<?php
namespace Svea\Maksuturva\Controller\Index;

class Cancel extends \Svea\Maksuturva\Controller\Maksuturva
{
    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $params = $this->getRequest()->getParams();

        if (empty($pmt_id)) {
            $this->messageManager->addError(__('Unknown error on Svea payment module.'));
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH));
            return;
        }

        $this->_maksuturvaHelper->sveaLoggerDebug("Cancel action request for payment " . $pmt_id);

        $order = $this->getLastedOrder();
        $payment = $this->getPayment();
        $additional_data = json_decode($payment->getAdditionalData(), true);

        if ($additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->_redirect('checkout/cart');
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            $order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
            $order->cancel();
            $order->addStatusHistoryComment(__('Payment canceled in Svea Payments'), 'pay_aborted');

            if($this->getConfigData('canceled_order_status')){
                $canceledStatus = $this->getConfigData('canceled_order_status');
            }else{
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
            }
            $order->setStatus($canceledStatus, true, __('You have cancelled your payment in Svea Payments.'));
            $order->save();

            $this->messageManager->addError(__('You have cancelled your payment in Svea Payments.'));
        } else {
            $this->messageManager->addError(__('Unable to cancel order that has already been paid.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $this->_redirect('checkout/cart');
    }

}