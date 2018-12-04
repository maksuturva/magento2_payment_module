<?php
namespace Piimega\MaksuturvaMasterpass\Plugin\Quote\Model;

class QuotePlugin
{
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;
    }

    public function aroundAssignCustomer($subject, $procese, $customer)
    {
        if($this->_checkoutSession->getData('isMasterpassInUse')){
            $shippingAddress = $this->_checkoutSession->getQuote()->getShippingAddress();
            $billingAddress = $this->_checkoutSession->getQuote()->getBillingAddress();
            $result = $subject->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);
        }else{
            $result = $procese($customer);
        }
        return $result;
    }
}