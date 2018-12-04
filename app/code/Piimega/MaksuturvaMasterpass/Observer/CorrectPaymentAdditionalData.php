<?php
namespace Piimega\MaksuturvaMasterpass\Observer;

use Magento\Framework\Event\ObserverInterface;

class CorrectPaymentAdditionalData implements ObserverInterface
{

    protected $serializer;

    public function __construct (
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->serializer  =  $serializer;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        if(is_array($additionalData = $payment->getAdditionalData())){
            $payment->setAdditionalData($this->serializer->serialize($additionalData));
        }
    }
}