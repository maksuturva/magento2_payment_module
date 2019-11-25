<?php
namespace Svea\Maksuturva\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;

class SaveMaksuturvaPreselectedPaymentMethod implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $quote = $observer->getQuote();
        if(strpos($quote->getPayment()->getData('method'), 'maksuturva') !== false){
            $additionalInformation = $quote->getPayment()->getData('additional_information');
            if($additionalInformation && isset($additionalInformation['maksuturva_preselected_payment_method'])){
                $order->setMaksuturvaPreselectedPaymentMethod($additionalInformation['maksuturva_preselected_payment_method']);
            }
        }else{
            $order->setMaksuturvaPreselectedPaymentMethod('');
        }
        return $this;
    }
}