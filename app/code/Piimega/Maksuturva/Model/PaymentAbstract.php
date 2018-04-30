<?php
namespace Piimega\Maksuturva\Model;

use Magento\Framework\UrlInterface;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Framework\Config\CacheInterface;

abstract class PaymentAbstract extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    const ERROR_INVALID_HASH = 'invalid_hash_error';
    const ERROR_EMPTY_FIELD = 'empty_field_error';
    const ERROR_VALUES_MISMATCH = 'values_mismatch_error';
    const ERROR_SELLERCOSTS_VALUES_MISMATCH = 'sellercosts_values_mismatch_error';
    const MAKSUTURVA_PRESELECTED_PAYMENT_METHOD = "maksuturva_preselected_payment_method";
    const MAKSUTURVA_PRESELECTED_PAYMENT_METHOD_DESCRIPTION = "maksuturva_preselected_payment_method_description";
    const MAKSUTURVA_TRANSACTION_ID = "maksuturva_transaction_id";

    const PARENT_CODE = 'maksuturva_payment';
    protected $_allowCurrencyCode = array("EUR");
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded = true;
    protected $_formBlockType = 'maksuturva/form';


    protected  $_dataObject;
    protected $_orderFactory;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $_coreHelper;
    protected $_taxConfig;
    protected $_calculationFactory;
    protected $_encryptor;
    protected $_implementation;
    protected $implementation;
    protected $_currentOrder;
    protected $_methodResourceModel;
    protected $_methods = [];
    protected $cache;
    protected $checkoutSession;
    protected $cart;

    public function __construct(
    	\Magento\Framework\Model\Context $context,
    	\Magento\Framework\Registry $registry,
    	\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    	\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    	\Magento\Payment\Helper\Data $paymentData,
    	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    	\Magento\Payment\Model\Method\Logger $logger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $session,
        UrlInterface $urlInterface,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Piimega\Maksuturva\Model\Gateway\Implementation $implementation,
        \Piimega\Maksuturva\Model\ResourceModel\Method $methodResourceModel,
        CacheInterface $cache,
        \Magento\Checkout\Model\Cart $cart,
    	array $data = []
    ) {
    	parent::__construct(
    			$context,
    			$registry,
    			$extensionFactory,
    			$customAttributeFactory,
    			$paymentData,
    			$scopeConfig,
    			$logger
    	);
        $this->_urlBuilder = $urlInterface;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_implementation = $implementation;
        $this->_methodResourceModel = $methodResourceModel;
        $this->cart = $cart;
        $this->cache = $cache;
        $this->checkoutSession = $session;
    }

    abstract public function getMethods();

    public function getOrderPlaceRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('maksuturva/index/redirect', array('_secure' => true));
    }

    public function getCheckoutFormFields()
    {
        $implementation = $this->getGatewayImplementation();
        $implementation->setOrder($this->getOrder());
        return $implementation->getFormFields();
    }

    public function getGatewayImplementation()
    {
        if($this->implementation === null){
            $this->implementation = $this->_implementation->setConfig($this->getConfigs())->setPayment($this->getOrder()->getPayment());
        }
        return $this->implementation;
    }

    protected function getOrder()
    {
        if ($this->_currentOrder == null)

        {
            $this->_currentOrder = $this->_orderFactory->create();
            $this->_currentOrder->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        }
        return $this->_currentOrder;
    }

    public function setOrder(\Magento\Sales\Model\Order $order)
    {
        if (is_object($order) && $order->getId())
        {
            $this->_currentOrder = $order;
        }else{
            $this->_currentOrder = null;
        }
    }

    public function getConfigs()
    {
        $config = array(
            'sandbox' => intval($this->getBaseConfigData('sandboxmode')),
            'commencoding' => $this->getBaseConfigData('commencoding'),
            'paymentdue' => intval($this->getBaseConfigData('paymentdue')),
            'keyversion' => $this->getBaseConfigData('keyversion'),
            'preselect_payment_method' => $this->getBaseConfigData('preselect_payment_method'),
        );
        if ($config['sandbox']) {
            $config['sellerId'] = $this->getBaseConfigData('test_sellerid');
            $config['secretKey'] = $this->getBaseConfigData('test_secretkey');
            $config['commurl'] = $this->getBaseConfigData('test_commurl');
        } else {
            $config['sellerId'] = $this->getBaseConfigData('sellerid');
            $config['secretKey'] = $this->getBaseConfigData('secretkey');
            $config['commurl'] = $this->getBaseConfigData('commurl');
        }
        return $config;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    public function getCheckout()
    {
        return $this->checkoutSession;
    }

    public function getPaymentRequestUrl()
    {
        return $this->getGatewayImplementation()->getPaymentRequestUrl();
    }

    protected function _getPaymentMethods()
    {
        $quoteTotal = $this->cart->getQuote()->getGrandTotal();
        $cacheKey = "MAKSUTURVA_PAYMENT_METHODS_" . number_format($quoteTotal, 4, "_", "");

        if ($cachedData = $this->cache->load($cacheKey)) {
            $this->_methods = unserialize($cachedData);
        } else {
            $this->_methods = $this->getGatewayImplementation()->getPaymentMethods($quoteTotal);
            if ($this->_methods) {
                $this->cache->save(serialize($this->_methods), $cacheKey, array("MAKSUTURVA"), 60 * 5);
                //
                if ($this->_methods && count($this->_methods) > 0){
                    foreach($this->_methods as $method){
                        $this->_methodResourceModel->insert($method);
                    }
                }
            }
        }
        return $this->_methods;
    }

    public function canUseCheckout()
    {
        if ($this->getBaseConfigData('preselect_payment_method')) {
            if ($this->getMethods()) {
                return $this->_canUseCheckout;
            } else {
                return false;
            }
        } else {
            return $this->_canUseCheckout;
        }
    }

    public function canUseForCurrency($currencyCode)
    {
        return in_array($currencyCode, $this->_allowCurrencyCode);
    }

    public function isDelayedCaptureCase($method)
    {
        $delayedMethods = $this->getBaseConfigData('delayed_capture');
        $delayedMethods = explode(',', $delayedMethods);

        return in_array($method, $delayedMethods);
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $getAdditionalDataUnserialized = $payment->getAdditionalData();
        if (!is_array($getAdditionalDataUnserialized)) {
            $additional_data = $payment->getAdditionalData();
        } else {
            $additional_data = $getAdditionalDataUnserialized;
        }
        
        $method = $additional_data[self::MAKSUTURVA_PRESELECTED_PAYMENT_METHOD];

        if ($this->isDelayedCaptureCase($method)) {
            $result = $this->getGatewayImplementation()->addDeliveryInfo($payment);

            if(isset($result['pkg_id'])){
                $payment->setTransactionId($result['pkg_id']);
            }
            $payment->setIsTransactionClosed(1);
            $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $result);
        }
        return $this;
    }

    public function getBaseConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'maksuturva_config/' . self::PARENT_CODE . '/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function _getAllowedMethods()
    {
        $allowedMethods =[];
        if($this->getBaseConfigData('preselect_payment_method')){
            $allowedMethodsString = $this->getConfigData('method_filter');
            $allowedMethodsString = preg_replace('/\s+/', '', $allowedMethodsString);
            $allowedMethods = explode(";", $allowedMethodsString);
        }
        return $allowedMethods;
    }

    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        // Implement postRequest() method.
    }
}
