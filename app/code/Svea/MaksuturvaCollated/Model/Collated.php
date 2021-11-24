<?php
namespace Svea\MaksuturvaCollated\Model;

use Svea\MaksuturvaCollated\Model\CollatedConfigProvider;
/**
 * Class Collated
 *
 * @package Svea\MaksuturvaCollated\Model
 */
class Collated extends \Svea\Maksuturva\Model\PaymentAbstract
{
    protected $_code = 'maksuturva_collated_payment';
    protected $_allowedMethods = [];

    /**
     * @return array
     */
    public function getMethods()
    {
        if(!$this->_methods){
            $this->_methods = $this->_getPaymentMethods();
        }
        if ($this->isSubpaymentsEnabled()) {
            return $this->populateSubpaymentMethods();
        }
        foreach($this->_methods as $method){
            if(in_array($method->code, $this->getAllowedMethods())){
                $this->_allowedMethods[] = $method;
            }
        }
        return $this->_allowedMethods;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    private function isSubpaymentsEnabled()
    {
        /* always enabled since 1.7.1 */
        return true;
        //return (bool)$this->getConfigData('subpayments_active');
    }

    /**
     * @return array|false|string[]
     */
    private function getAllowedSubpaymentMethods()
    {
        $allowedMethods =[];
        foreach (CollatedConfigProvider::SUBPAYMENT_STEPS as $subpayment) {
            $allowedMethodsString = $this->getConfigData('maksuturva_collated_subpayments/'.$subpayment.'_method_filter');
            $allowedMethodsString = preg_replace('/\s+/', '', $allowedMethodsString);
            $allowedMethods[$subpayment] = explode(";", $allowedMethodsString);
        }
        return $allowedMethods;
    }

    private function getAllowedMethods()
    {
        return $this->_getAllowedMethods();
    }

    /**
     * @return array
     */
    private function populateSubpaymentMethods(): array
    {
        $allowedSubpaymentMethods = $this->getAllowedSubpaymentMethods();
        foreach($this->_methods as $method){
            foreach ($allowedSubpaymentMethods as $paymentMethodName => $subpaymentMethod) {
                if(in_array($method->code, $subpaymentMethod)){
                    $method->parentMethod = $paymentMethodName;
                    $this->_allowedMethods[$paymentMethodName][] = $method;
                }
            }
        }
        return $this->_allowedMethods;
    }
}
