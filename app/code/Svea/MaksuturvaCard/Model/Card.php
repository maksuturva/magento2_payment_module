<?php
namespace Svea\MaksuturvaCard\Model;

class Card extends \Svea\Maksuturva\Model\PaymentAbstract
{
    protected $_code = 'maksuturva_card_payment';
    protected $_allowedMethods = [];

    public function getMethods()
    {
        if(!$this->_methods){
            $this->_methods = $this->_getPaymentMethods();
        }
        foreach($this->_methods as $method){
            $allowedMethods = $this->_getAllowedMethods();
            if(in_array($method->code, $allowedMethods)){
                $this->_allowedMethods[] = $method;
            }
        }
        return $this->_allowedMethods;
    }

    public function getTitle()
    {
        return $this->getConfigData('title');
    }
}
