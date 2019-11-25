<?php
namespace Svea\MaksuturvaMasterpass\Plugin\Checkout\Model;

class DefaultConfigProviderPlugin
{
    protected $checkoutSession;
    protected $addressMapper;
    protected $addressConfig;
    protected $urlBuilder;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        $this->urlBuilder = $urlBuilder;
    }

    public function aroundGetConfig($subject, $procese)
    {
        $result = $procese();
        $deliveryAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        $billingAddress = $this->checkoutSession->getQuote()->getBillingAddress();
        //prevent js exception if masterpassData is not be defined
        $result['masterpassData'] = [];
        if($this->checkoutSession->getData('isMasterpassInUse')) {

            //built masterpassData addresses data
            if($this->checkoutSession->getQuote()->isVirtual()){
                //Magento will use billing address as shipping address is current quote is virtual quote
                $result['masterpassData']['isVirtual'] = 1;
                $result['masterpassData']['addresses']['billing'] = $this->builtAddress($billingAddress);
            }else{
                $result['masterpassData']['isVirtual'] = 0;
                $result['masterpassData']['addresses']['shipping'] = $this->builtAddress($deliveryAddress);
                $result['masterpassData']['addresses']['billing'] = $this->builtAddress($billingAddress);
            }

            //use only masterpass address
            $result['customerData'] = [];

            $result['checkoutUrl'] = $this->getMasterpassCheckoutUrl();
        }
        return $result;
    }

    public function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    protected function builtAddress($addressObj)
    {
        $address = [];
        $fakeCustomerAddressDataModel = $addressObj->getDataModel();
        foreach ($addressObj->getData() as $key => $val) {
            //$address->getData('street') is not as same as  $address->getStreet()
            //guarantee correct value in further process
            $correctVal = $this->getCorrectValue($addressObj, $key);
            //set value to be used in fronted and be generated inline address
            $fakeCustomerAddressDataModel->setData($key, $correctVal);
            $address[$key] = $correctVal;
        }
        //built the fake customer address
        $address['inline'] = $this->getCustomerAddressInline($fakeCustomerAddressDataModel);
        return $address;
    }

    protected function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = $array[0];
        $len=count($array);
        if($len>1)
        {
            for($i=1;$i<$len;$i++)
            {
                $result.= ucfirst($array[$i]);
            }
        }
        return $result;
    }

    public function getCorrectValue($obj, $key)
    {
        $_key = 'get' . ucfirst($this->toCamelCase($key));
        return $obj->$_key();
    }

    public function getMasterpassCheckoutUrl()
    {
        return $this->urlBuilder->getUrl('masterpass/checkout');
    }
}