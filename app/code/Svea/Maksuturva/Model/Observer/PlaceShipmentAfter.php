<?php
namespace Svea\Maksuturva\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlaceShipmentAfter implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        if($order && $order->getId()){
            $order->getPayment()->getMethodInstance()->getGatewayImplementation()->addDeliveryInfo($order->getPayment());
        }
    }
}