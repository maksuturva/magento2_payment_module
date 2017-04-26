<?php
namespace Piimega\Maksuturva\Controller\Index;


class Error extends \Piimega\Maksuturva\Controller\Maksuturva
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
        array $data = []
    )
    {
        parent::__construct($context, $orderFactory, $logger, $scopeConfig, $quoteRepository, $checkoutsession, $maksuturvaHelper, $data);
    }


    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $params = $this->getRequest()->getParams();

        $order = $this->getLastedOrder();

        $payment = $order->getPayment();
        $additional_data = unserialize($payment->getAdditionalData());

        $paramsArray = $this->getRequest()->getParams();

        if (array_key_exists('pmt_id', $paramsArray)) {
            $this->messageManager->addError(__('Maksuturva returned an error on your payment.'));
        } else {
            switch ($paramsArray['type']) {
                case \Piimega\Maksuturva\Model\Payment::ERROR_INVALID_HASH:
                    $this->messageManager->addError(__('Invalid hash returned'));
                    break;

                case \Piimega\Maksuturva\Model\Payment::ERROR_EMPTY_FIELD:
                    $this->messageManager->addError(__('Gateway returned an empty field') . ' ' . $paramsArray['field']);
                    break;

                case \Piimega\Maksuturva\Model\Payment::ERROR_VALUES_MISMATCH:
                    $this->messageManager->addError(__('Value returned from Maksuturva does not match:') . ' ' . @$paramsArray['message']);
                    break;

                case \Piimega\Maksuturva\Model\Payment::ERROR_SELLERCOSTS_VALUES_MISMATCH:
                    $this->messageManager->addError(__('Shipping and payment costs returned from Maksuturva do not match.') . ' ' . $paramsArray['message']);
                    break;

                default:
                    $this->messageManager->addError(__('Unknown error on maksuturva payment module.'));
                    break;
            }
        }

        if ($additional_data[\Piimega\Maksuturva\Model\Payment::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->_redirect('checkout/cart');
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            if (isset($paramsArray['type']) && $paramsArray['type'] == \Piimega\Maksuturva\Model\Payment::ERROR_SELLERCOSTS_VALUES_MISMATCH) {
                $order->addStatusHistoryComment(__('Mismatch in seller costs returned from Maksuturva. New sellercosts: ' . $paramsArray["new_sellercosts"] . ' EUR,' . ' was ' . $paramsArray["old_sellercosts"] . ' EUR.'));
            } else {
                $order->addStatusHistoryComment(__('Error on Maksuturva return'));
            }

            $order->save();
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_redirect('checkout/cart');
    }

}