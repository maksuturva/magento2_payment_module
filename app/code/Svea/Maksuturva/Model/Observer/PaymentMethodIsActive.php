<?php
namespace Svea\Maksuturva\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Svea\Maksuturva\Model\PaymentAbstract;

class PaymentMethodIsActive implements ObserverInterface {

    protected $_coreRegistry;

    public function __construct (\Magento\Framework\Registry $registry) {
        $this->_coreRegistry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $event  = $observer->getEvent();
        $method = $event->getMethodInstance();

        if ($method instanceof PaymentAbstract) {
            $result = $event->getResult();
            if (is_object($method)) {
                $methodCode = $method->getCode();
                //show only maksuturva_base of maksuturva payments if preselect_payment_method is disabled
                if($method->getBaseConfigData('preselect_payment_method')){
                    if (mb_strlen($methodCode, 'UTF-8') > 0 && (strpos($methodCode, 'maksuturva') !== false)) {
                        $registryKey = $methodCode.'_is_active_checked';
                        if (!$this->_coreRegistry->registry($registryKey)) {
                            $this->_coreRegistry->register($registryKey, true, true);
                            $result             = $event->getResult();
                            if(strpos($methodCode, 'maksuturva_base') !== false){
                                $result->setData('is_available', false);
                            }else{
                                if (!$method->getMethods()) {
                                    $result->setData('is_available', false);
                                }else{
                                    $result->setData('is_available', true);
                                }
                            }
                        }
                    }
                }elseif(strpos($methodCode, 'maksuturva') !== false && $methodCode != "maksuturva_masterpass"){
                    if(strpos($methodCode, 'maksuturva_base') !== false){
                        $result->setData('is_available', true);
                    }else{
                        $result->setData('is_available', false);
                    }
                }
            }
        }
    }
}
