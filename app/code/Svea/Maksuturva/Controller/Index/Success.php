<?php
namespace Svea\Maksuturva\Controller\Index;

class Success extends \Svea\Maksuturva\Controller\Maksuturva
{
    protected $orderSender;
    protected $_resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Svea\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        array $data = []
    )
    {
        parent::__construct($context, $orderFactory, $scopeConfig, $quoteRepository, $checkoutsession, $maksuturvaHelper, $orderRepository, $searchCriteriaBuilder, $sortOrderBuilder, $orderPaymentRepository, $data);
        $this->_resultPageFactory = $resultLayoutFactory;
        $this->orderSender = $orderSender;
    }

    /***
     * Handle execute error messaging. If callback, use http response codes, otherwise
     * redirect to error page
     */
    private function errorOccured($iscallback, $result_array, $httpcode, $msg, $e) {
        if (!empty($e)) {
            $this->getHelper()->sveaLoggerError($msg . $e);
        } else {
            $this->getHelper()->sveaLoggerError($msg);
        }

        if(!$iscallback) {
            $this->_redirect('maksuturva/index/error', $result_array);
        } else {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', $code, true)
                ->setHeader('Content-Type', 'text/html') 
                ->setBody($msg);
        }
    }

    /**
     * Execute success action
     * 
     * This method is called when user returns to the webshop after payment is completed
     * successfully and also to process the Svea Payments callbacks 
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $callback_header = $this->getRequest()->getHeader('X-Svea-Callback');
        $iscallback = false;

        if (!empty($callback_header) && $callback_header==="true" ) {
            $this->getHelper()->sveaLoggerDebug("Callback webhook requested.");  
            $iscallback=true; 
        }

        foreach ($this->mandatoryFields as $field) {
            if (array_key_exists($field, $params)) {
                $values[$field] = $params[$field];
            } else {
                $this->errorOccured($iscallback,
                    array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_EMPTY_FIELD, 'field' => $field),
                    400, "Mandatory fields are missing.", null);
                return;
            }
        }

        try {
            $order = $this->getOrderByPaymentId($values['pmt_id']);
        } catch (\Exception $e) {
            $this->errorOccured($iscallback, 
                array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => __('Order matching the payment id could not be found.')),
                404, "Order " . $values['pmt_id'] . " not found.", $e);
            return;
        }

        if ($order->getBaseTotalDue()<0.01) {
            $noticemsg = "Callback received but order " . $order->getIncrementId() . " is marked as paid already.";
            $this->getHelper()->sveaLoggerInfo($noticemsg);  
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', 200, true)
                ->setHeader('Content-Type', 'text/html') 
                ->setBody($noticemsg);
            return;
        }

        $this->_checkoutSession
            ->setLastOrderId($order->getId())
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        if(!$this->validateReturnedOrder($order, $params)){
            $this->errorOccured($iscallback,
                array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => __('Unknown error on Svea Payment module.')),
                500, "Validation error for order " . $order->getIncrementId(), null);
            return;
        }

        $method = $order->getPayment()->getMethodInstance();
        $implementation = $method->getGatewayImplementation();
        $calculatedHash = $implementation->generateReturnHash($values);

        if ($values['pmt_hash'] != $calculatedHash) {
            $this->errorOccured($iscallback,
                array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_INVALID_HASH),
                405, "Response message hash is invalid for order " . $order->getIncrementId(), null);
            return;
        }

        $implementation->setOrder($order);
        $implementation->setPayment($order->getPayment());

        if (!$order->canInvoice()) {
            if (!$iscallback) {
                $this->messageManager->addError(__('Your order is not valid or it is paid already. The order status is ' . $order->getState()) );
                $this->_redirect('checkout/cart');
            } else {
                $this->getHelper()->sveaLoggerInfo("Callback for order " . $order->getIncrementId() . 
                    " and no need to be updated anymore. The current order status " . $order->getState() . " and state " . $order->getState());
            }
            return;
        }

        $form = $implementation->getForm();
        $ignore = array("pmt_hash", "pmt_escrow", "pmt_paymentmethod", "pmt_reference", "pmt_sellercosts");
        foreach ($values as $key => $value) {
            if (in_array($key, $ignore)) {
                continue;
            }
            if ($form->{$key} != $value) {
                $this->errorOccured($iscallback,
                    array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => urlencode("different $key: $value != " . $form->{$key})),
                    500, "Values mismatch for order " . $order->getIncrementId(), null);
                return;
            }
        }

        if ($form->{'pmt_sellercosts'} > $values['pmt_sellercosts']) {
            $this->errorOccured($iscallback,
                array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_SELLERCOSTS_VALUES_MISMATCH, 
                    'message' => urlencode("Payment method returned shipping and payment costs of " . $values['pmt_sellercosts'] . " EUR. YOUR PURCHASE HAS NOT BEEN SAVED. Please contact the web store."), 
                    'new_sellercosts' => $values['pmt_sellercosts'], 
                    'old_sellercosts' => $form->{'pmt_sellercosts'}),
                500, "Sellercosts mismatch for order " . $order->getIncrementId(), null);
            return;
        }

        if ($order->getId()) {
            $isDelayedCapture = $method->isDelayedCaptureCase($values['pmt_paymentmethod']);
            $statusText = $isDelayedCapture ? "authorized" : "captured";

            if ($form->{'pmt_sellercosts'} != $values['pmt_sellercosts']) {
                $sellercosts_change = $values['pmt_sellercosts'] - $form->{'pmt_sellercosts'};
                if ($sellercosts_change > 0) {
                    $msg = __("Payment %1 by Svea Payments. NOTE: Difference in the sellercosts + %2 EUR.", array($statusText, $sellercosts_change));
                } else {
                    $msg = __("Payment %1 by Svea Payments. NOTE: Difference in the sellercosts %2 EUR.", array($statusText, $sellercosts_change));
                }
            } else {
                $msg = __("Payment %1 by Svea Payments", $statusText);
            }

            try {
                if (!$isDelayedCapture) {
                    $this->_createInvoice($order);
                }
                if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                        $this->getHelper()->statusQuery($order);
                }
                if($this->getConfigData('paid_order_status')){
                    $processStatus = $this->getConfigData('paid_order_status');
                }else{
                    $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                }

                $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($processState, true, $msg, false);
                $order->setStatus($processStatus, true, $msg, false);
                $order->save();
                $this->getHelper()->sveaLoggerInfo("" . $order->getIncrementId() . 
                    " status updated with message '" . $msg . 
                    "', status " . $processStatus . 
                    " and state " . $processState);
                
                $this->disableQuote($order);

                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                return; 
            } catch (Exception $e) {
                $this->getHelper()->sveaLoggerError("Order " . $order->getIncrementId() . " status update failed. " . $e);
            }
        }

        $this->getHelper()->sveaLoggerError("Unknown success controller error for " .  $order->getIncrementId());
        $this->_redirect('maksuturva/index/error', array('type' => 9999));
        $this->getResponse()->setBody($this->_resultPageFactory->create()->getLayout()->createBlock('Svea\Maksuturva\Block\Form\Maksuturva')->toHtml());
    }

}