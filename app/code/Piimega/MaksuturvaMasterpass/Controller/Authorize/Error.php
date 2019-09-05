<?php
namespace Piimega\MaksuturvaMasterpass\Controller\Authorize;

class Error extends \Piimega\MaksuturvaMasterpass\Controller\AbstractController
{
    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $order = $this->getLastedOrder();

        $payment = $this->getPaymentMethod();
        $additional_data = $this->getHelper()->getPaymentAdditionData($payment);

        $paramsArray = $this->getRequest()->getParams();

        if (array_key_exists('pmt_id', $paramsArray)) {
            $this->messageManager->addError(__('Maksuturva returned an error on your payment.'));
        } else if(isset($paramsArray['type'])){
            switch ($paramsArray['type']) {
                case \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_INVALID_HASH:
                    $this->messageManager->addError(__('Invalid hash returned'));
                    break;

                case \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_EMPTY_FIELD:
                    $this->messageManager->addError(__('Gateway returned an empty field') . ' ' . $paramsArray['field']);
                    break;

                case \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH:
                    $this->messageManager->addError(__('Value returned from Maksuturva does not match:') . ' ' . @$paramsArray['message']);
                    break;

                case \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_SELLERCOSTS_VALUES_MISMATCH:
                    $this->messageManager->addError(__('Shipping and handling costs returned by Maksuturva do not match.') . ' ' . $paramsArray['message']);
                    break;

                default:
                    $this->messageManager->addError(__('Unknown error on maksuturva payment module.'));
                    break;
            }
        }

        if ($additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->_redirect('checkout/cart');
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            if (isset($paramsArray['type']) && $paramsArray['type'] == \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_SELLERCOSTS_VALUES_MISMATCH) {
                $order->addStatusHistoryComment(__('Mismatch in seller costs returned from Maksuturva. New sellercosts: ' . $paramsArray["new_sellercosts"] . ' EUR,' . ' was ' . $paramsArray["old_sellercosts"] . ' EUR.'));
            } else {
                $order->addStatusHistoryComment(__('Error in Maksuturva return'));
            }

            $order->save();
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_redirect('checkout/cart');
    }
}