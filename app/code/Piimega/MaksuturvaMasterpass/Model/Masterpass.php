<?php
namespace Svea\MaksuturvaMasterpass\Model;

class Masterpass extends \Magento\Payment\Model\Method\AbstractMethod
{
    const MAKSUTURVA_MASTERPASS_METHOD_CODE = 'FI55';
    const ERROR_MAKSUTURVA_RETURN = 'error_maksuturva_return';
    const ERROR_COMMUNICATION_FAILED = 'error_communication_failed';
    const MASTERPASS_METHOD_CODE = 'maksuturva_masterpass';

    protected $_code = 'maksuturva_masterpass';
    protected $_allowedMethods = [];
    protected $config;
    protected $gateway;
    protected $masterpassHelper;
    protected $quoteRepository;
    protected $_regionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Svea\MaksuturvaMasterpass\Model\Gateway\Implementation $implementationGateway,
        \Svea\MaksuturvaMasterpass\Helper\Data $masterpassHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->gateway = $implementationGateway->setConfig($this->getConfigs());
        $this->masterpassHelper = $masterpassHelper;
        $this->quoteRepository = $quoteRepository;
        $this->_regionFactory = $regionFactory;
    }

    public function getConfigs()
    {
        if ($this->config == null) {
            $this->config = array(
                'sandbox' => intval($this->getConfigData('sandboxmode')),
                'keyversion' => $this->getConfigData('keyversion'),
                'paymentdue' => $this->getConfigData('paymentdue'),
                'active' => intval($this->getConfigData('active'))
            );

            $this->config['commencoding'] = 'UTF-8';

            if ($this->config['sandbox']) {
                $this->config['commurl'] = $this->getConfigData('test_commurl');
                $this->config['sellerId'] = $this->getConfigData('test_sellerid');
                $this->config['secretKey'] = $this->getConfigData('test_secretkey');
                $this->config['callbackUrlWorkaround'] = $this->getConfigData('callback_url_workaround');
            } else {
                $this->config['commurl'] = $this->getConfigData(('commurl'));
                $this->config['sellerId'] = $this->getConfigData('sellerid');
                $this->config['secretKey'] = $this->getConfigData('secretkey');
            }
        }
        return $this->config;
    }

    public function getConfigValue($key)
    {
        $config = $this->getConfigs();

        if (isset($config[$key])) {
            return $config[$key];
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    public function getGateway()
    {
        return $this->gateway;
    }

    public function getFormFields()
    {
        return $this->getGateway()->getFormFields();
    }

    public function setAddressesFromXml($xmlElement)
    {
        $quote = $this->getQuote();
        $quote->setCustomerEmail((string)$xmlElement->pmtq_buyeremail);

        $buyerAddress = $this->setBillAddress($xmlElement);
        $deliveryAddress = $this->setDeliveryAddress($xmlElement);

        $serializedBuyerData = $this->masterpassHelper->serializeAddress($buyerAddress);
        $serializeDeliveryData = $this->masterpassHelper->serializeAddress($deliveryAddress);
        $isAddressSame = strcmp($serializedBuyerData, $serializeDeliveryData) == 0;

        $deliveryAddress->setSameAsBilling($isAddressSame);
        $quote->collectTotals();

        $buyerAddress->save();
        $deliveryAddress->save();
        //save responsed data from Masterpass
        $this->quoteRepository->save($quote);

        return $this;
    }

    public function getQuote()
    {
        return $this->masterpassHelper->getCheckoutSession()->getQuote();
    }

    protected function setBillAddress($xmlElement)
    {
        $quote = $this->getQuote();
        $buyerAddress = $quote->getBillingAddress();

        $this->masterpassHelper->parseName((string)$xmlElement->pmtq_buyername, $buyerFirstname, $buyerLastname);

        $buyerAddress->setFirstname($buyerFirstname);
        $buyerAddress->setLastname($buyerLastname);
        $buyerAddress->setStreet([(string)$xmlElement->pmtq_buyeraddress1, (string)$xmlElement->pmtq_buyeraddress2]);
        $buyerAddress->setPostcode((string)$xmlElement->pmtq_buyerpostalcode);
        $buyerAddress->setCity((string)$xmlElement->pmtq_buyercity);
        $buyerAddress->setCountryId((string)$xmlElement->pmtq_buyercountry);
        $buyerAddress->setTelephone((string)$xmlElement->pmtq_buyerphone);
        $buyerAddress->setEmail((string)$xmlElement->pmtq_buyeremail);
        $buyerAddress->setQuoteId($quote->getEntityId());
        $buyerAddress->setAddressType('billing');
        $buyerAddress->setPaymentMethod($this->_code);
        $buyerAddress->setCustomerAddressId(null);
        $regionId = $this->getRegionIdByMaksuturvaCode((string)$xmlElement->pmtq_buyercountrysubdivision);
        $buyerAddress->setRegionId($regionId);

        $quote->setBillingAddress($buyerAddress);
        return $buyerAddress;
    }

    protected function setDeliveryAddress($xmlElement)
    {
        $quote = $this->getQuote();
        $deliveryAddress = $quote->getShippingAddress();
        $this->masterpassHelper->parseName((string)$xmlElement->pmtq_deliveryname, $deliveryFirstname, $deliveryLastname);

        $deliveryAddress->setFirstname($deliveryFirstname);
        $deliveryAddress->setLastname($deliveryLastname);
        $deliveryAddress->setStreet([(string)$xmlElement->pmtq_deliveryaddress1, (string)$xmlElement->pmtq_deliveryaddress2]);
        $deliveryAddress->setPostcode((string)$xmlElement->pmtq_deliverypostalcode);
        $deliveryAddress->setCity((string)$xmlElement->pmtq_deliverycity);
        $deliveryAddress->setCountryId((string)$xmlElement->pmtq_deliverycountry);
        $deliveryAddress->setTelephone((string)$xmlElement->pmtq_buyerphone);
        $deliveryAddress->setEmail((string)$xmlElement->pmtq_buyeremail);
        $deliveryAddress->setQuoteId($quote->getEntityId());
        $deliveryAddress->setCollectShippingRates(true);
        $deliveryAddress->setAddressType('shipping');
        $deliveryAddress->setPaymentMethod($this->_code);
        $deliveryAddress->setCustomerAddressId(null);
        $regionId = $this->getRegionIdByMaksuturvaCode((string)$xmlElement->pmtq_deliverycountrysubdivision);
        $deliveryAddress->setRegionId($regionId);

        $quote->setShippingAddress($deliveryAddress);
        return $deliveryAddress;
    }

    /**
     * Returns a magento region id if can be explained by maksuturva code
     *
     * @param null $code
     * @return int|mixed
     */
    public function getRegionIdByMaksuturvaCode($code = null)
    {
        $regionId = 0;
        $codes    = explode("-", $code);
        if (sizeof($codes) == 2) {
            $countryCode    = $codes[0];
            $regionCode     = $codes[1];
            $region         = $this->_regionFactory->create();
            $regionId       = $region->loadByCode($regionCode, $countryCode)->getId();
        }

        return $regionId;
    }

}

