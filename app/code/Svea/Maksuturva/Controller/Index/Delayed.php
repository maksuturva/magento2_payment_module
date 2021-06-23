<?php
namespace Svea\Maksuturva\Controller\Index;

class Delayed extends \Svea\Maksuturva\Controller\Maksuturva
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $order = $this->getLastedOrder();

        $this->_maksuturvaHelper->sveaLoggerDebug("Delayed action request");

        if(!$this->validateReturnedOrder($order, $params)){
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH, 'message' => __('Unknown error on Svea Payments payment module.')));
            return;
        }

        if ($order->getId()) {
            $order = $this->getLastedOrder();

            if ($order->getId()) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true, __('Waiting for delayed payment confirmation from Svea Payments'))->save();
            }
            $this->disableQuote($order);
        }

        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
}