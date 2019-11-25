<?php
namespace Svea\MaksuturvaMasterpass\Observer;

use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActive implements ObserverInterface
{

    protected $_checkoutSession;

    public function __construct (
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_checkoutSession  =  $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event  = $observer->getEvent();
        $result = $event->getResult();
        $method = $event->getMethodInstance();

        //if masterpass payment is in use. then disable all other payment methods
        if(($this->_checkoutSession->getData('isMasterpassInUse') && $method->getCode() != "maksuturva_masterpass")
        || (!$this->_checkoutSession->getData('isMasterpassInUse') && $method->getCode() == "maksuturva_masterpass")){
            $result->setData('is_available', false);
        }
    }
}