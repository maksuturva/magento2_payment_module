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
        array $data = []
    )
    {
        parent::__construct($context, $orderFactory, $scopeConfig, $quoteRepository, $checkoutsession, $maksuturvaHelper, $orderRepository, $searchCriteriaBuilder, $sortOrderBuilder, $data);
        $this->_resultPageFactory = $resultLayoutFactory;
        $this->orderSender = $orderSender;
    }

    public function execute()
    {

        $params = $this->getRequest()->getParams();

        foreach ($this->mandatoryFields as $field) {
            if (array_key_exists($field, $params)) {
                $values[$field] = $params[$field];
            } else {
                $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_EMPTY_FIELD, 'field' => $field));
                return;
            }
        }
        //can not laod lastedRealOrderId like this way. Because of the quote was restoreQuoted
        //load order id according to quote id. The last item will be current order.
        $order = $this->getLastedOrder();

        if(!$this->validateReturnedOrder($order, $params)){
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => __('Unknown error on maksuturva payment module.')));
            return;
        }

        $method = $order->getPayment()->getMethodInstance();
        $implementation = $method->getGatewayImplementation();
        $calculatedHash = $implementation->generateReturnHash($values);

        if ($values['pmt_hash'] != $calculatedHash) {
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_INVALID_HASH));
            return;
        }

        $implementation->setOrder($order);
        if (!$order->canInvoice()) {
            $this->messageManager->addError(__('Your order is not valid or is already paid.'));
            $this->_redirect('checkout/cart');
            return;
        }

        $form = $implementation->getForm();
        $ignore = array("pmt_hash", "pmt_escrow", "pmt_paymentmethod", "pmt_reference", "pmt_sellercosts");
        foreach ($values as $key => $value) {
            if (in_array($key, $ignore)) {
                continue;
            }
            if ($form->{$key} != $value) {
                $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => urlencode("different $key: $value != " . $form->{$key})));
                return;
            }
        }

        if ($form->{'pmt_sellercosts'} > $values['pmt_sellercosts']) {
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_SELLERCOSTS_VALUES_MISMATCH, 'message' => urlencode("Payment method returned shipping and payment costs of " . $values['pmt_sellercosts'] . " EUR. YOUR PURCHASE HAS NOT BEEN SAVED. Please contact the web store."), 'new_sellercosts' => $values['pmt_sellercosts'], 'old_sellercosts' => $form->{'pmt_sellercosts'}));
            return;
        }

        if ($order->getId()) {

            $isDelayedCapture = $method->isDelayedCaptureCase($values['pmt_paymentmethod']);
            $statusText = $isDelayedCapture ? "authorized" : "captured";

            if ($form->{'pmt_sellercosts'} != $values['pmt_sellercosts']) {
                $sellercosts_change = $values['pmt_sellercosts'] - $form->{'pmt_sellercosts'};
                if ($sellercosts_change > 0) {
                    $msg = __("Payment %1 by Maksuturva. NOTE: Change in the sellercosts + %2 EUR.", array($statusText, $sellercosts_change));
                } else {
                    $msg = __("Payment %1 by Maksuturva. NOTE: Change in the sellercosts %2 EUR.", array($statusText, $sellercosts_change));
                }
            } else {
                $msg = __("Payment %1 by Maksuturva", $statusText);
            }

            if (!$isDelayedCapture) {
                $this->_createInvoice($order);
            }
            if (!$order->getEmailSent()) {
                try {
                    $this->orderSender->send($order);
                    $order->setEmailSent(true);
                    $this->_maksuturvaHelper->statusQuery($order);
                } catch (\Exception $e) {
                    $this->_maksuturvaHelper->maksuturvaLogger($e);
                }
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

            $this->disableQuote($order);

            $this->_redirect('checkout/onepage/success', array('_secure' => true));

            return;
        }

        $this->_redirect('maksuturva/index/error', array('type' => 9999));
        $this->getResponse()->setBody($this->_resultPageFactory->create()->getLayout()->createBlock('Svea\Maksuturva\Block\Form\Maksuturva')->toHtml());
    }

}