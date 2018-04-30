<?php
namespace Piimega\Maksuturva\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class AssignAdditionalInformation implements ObserverInterface
{
    const MAKSUTURVA_PRESELECTED_PAYMENT_METHOD = 'maksuturva_preselected_payment_method';
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment_model');
        $data = $observer->getEvent()->getData('data');

        if (isset($data['additional_data']) &&
            isset($data['additional_data']['extension_attributes']) &&
            is_object($data['additional_data']['extension_attributes']) &&
            $data['additional_data']['extension_attributes']->getMaksuturvaPreselectedPaymentMethod()
        ) {
            $payment->setAdditionalInformation(self::MAKSUTURVA_PRESELECTED_PAYMENT_METHOD, $data['additional_data']['extension_attributes']->getMaksuturvaPreselectedPaymentMethod());
        }
    }
}