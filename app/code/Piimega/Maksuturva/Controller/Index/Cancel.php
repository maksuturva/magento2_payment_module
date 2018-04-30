<?php
namespace Piimega\Maksuturva\Controller\Index;

class Cancel extends \Piimega\Maksuturva\Controller\Maksuturva
{
    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $params = $this->getRequest()->getParams();

        if (empty($pmt_id)) {
            $this->messageManager->addError(__('Unknown error on maksuturva payment module.'));
            $this->_redirect('maksuturva/index/error', array('type' => \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH));
            return;
        }

        $order = $this->getLastedOrder();
        $payment = $this->getPayment();
        $additional_data = $payment->getAdditionalData();

        if(!$this->validateReturnedOrder($order, $params)){
            $this->_redirect('maksuturva/index/error', array('type' => \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => __('Unknown error on maksuturva payment module.')));
            return;
        }

        if ($additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->_redirect('checkout');
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            $order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
            $order->cancel();
            $order->addStatusHistoryComment(__('Payment canceled on Maksuturva'), 'pay_aborted');

            if($this->getConfigData('canceled_order_status')){
                $canceledStatus = $this->getConfigData('canceled_order_status');
            }else{
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
            }
            $order->setStatus($canceledStatus, true, __('You have cancelled your payment on Maksuturva.'));
            $order->save();

            $this->messageManager->addError(__('You have cancelled your payment on Maksuturva.'));
        } else {
            $this->messageManager->addError(__('Unable cancel order that is already paid.'));
            $this->_redirect('checkout');
            return;
        }
        $this->_redirect('checkout');
    }

}