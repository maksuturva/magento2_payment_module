<?php
namespace Svea\MaksuturvaBase\Model;

class Base extends \Svea\Maksuturva\Model\PaymentAbstract
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

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return !empty($this->_getPaymentMethods());
    }
}
