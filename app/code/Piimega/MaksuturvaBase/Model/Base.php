<?php
namespace Piimega\MaksuturvaBase\Model;

class Base extends \Piimega\Maksuturva\Model\PaymentAbstract
{
    protected $_code = 'maksuturva_base_payment';
    protected $_allowedMethods = [];

    public function getMethods()
    {
        return $this->_allowedMethods;
    }

    public function getTitle()
    {
        return $this->getConfigData('title');
    }
}
