<?php
namespace Svea\MaksuturvaMasterpass\Model\Gateway;

class Implementation extends \Svea\Maksuturva\Model\Gateway\Implementation
{
    protected $baseForm;

    public function __construct(
        \Svea\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $calculationModel,
        \Svea\Maksuturva\Model\Form $maksuturvaForm,
        \Svea\MaksuturvaMasterpass\Model\Form\MasterpassBaseForm $masterpassBaseForm,
        \Magento\Framework\HTTP\Client\Curl $curl = null,
        \Magento\Framework\Event\ManagerInterface $eventManager 
    ) {
        parent::__construct($maksuturvaHelper, $storeManager, $scopeConfig, $urlBuilder, $groupFactory, $checkoutSession, $orderFactory, $taxHelper, $calculationModel, $maksuturvaForm, $curl, $eventManager);
        $this->baseForm = $masterpassBaseForm;
    }

    protected function getSellercosts()
    {
        //sellercosts is the shippingrate total in case
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $shippingCost = $shippingAddress->getData("base_shipping_amount");
        $shippingTaxR = $shippingAddress->getData("base_shipping_tax_amount");
        //the sellercosts is float type
        return floatval($shippingCost + $shippingTaxR);
    }

    protected function _getForm($fieldBuilder, $form)
    {
        if (! $this->form) {
            $fields = $fieldBuilder->build();
            $this->form = $form->setConfig(array(
                'secretkey' => $this->secretKey,
                'options' => $fields,
                'encoding' => $this->commEncoding,
                'url' => $this->commUrl
            ));
        }

        return $this->form;
    }

    protected function getSellerId()
    {
        return $this->sellerId;
    }

    protected function getOrderFormFieldBuilder()
    {
        $order = $this->getOrder();

        if (! $order instanceof \Magento\Sales\Model\Order) {
            throw new \Exception('order not found');
        }

        $builder = $this->getMasterpassBaseForm($order);

        $builder->setBillingAddress($order->getBillingAddress());
        $builder->setShippingAddress($order->getShippingAddress());

        // Adding the shipping cost as a row
        $shippingDescription = ($order->getShippingDescription() ? $order->getShippingDescription() : 'Free Shipping');
        $shippingCost = $order->getShippingAmount();
        $shippingTax = $order->getShippingTaxAmount();
        $builder->addShippingCost($shippingCost, $shippingTax, $shippingDescription);

        return $builder;
    }

    protected function getQuoteFormFieldBuilder()
    {
        $quote = $this->getQuote();

        if (! $quote instanceof \Magento\Quote\Model\Quote) {
            throw new \Exception('quote not found');
        }

        return $this->getMasterpassBaseForm($quote);
    }

    protected function getMasterpassBaseForm($order)
    {
        $formFieldBuilder = $this->baseForm;

        $dueDate = date("d.m.Y", strtotime("+" . $this->paymentDue . " day"));

        $formFieldBuilder->addItems($order->getAllItems());
        $orderData = $order->getData();

        if ($order instanceof \Magento\Sales\Model\Order) {
            $discountAmount = $orderData["discount_amount"];
            $discountDescription = $orderData["discount_description"];
        } else {
            $discountAmount = $order->getShippingAddress()->getDiscountAmount();
            $discountDescription = $order->getShippingAddress()->getDiscountDescription();
        }
        if ($discountAmount != 0) {
            $formFieldBuilder->addDiscountItem($discountAmount, $discountDescription);
        }

        // store unique transaction id on payment object for later retrieval
        $payment = $order->getPayment();
        $pmt_id = $this->getMaksuturvaPmtId($order, $payment);
        $orderId = $this->getOrderId($order);

        $formFieldBuilder->addOrderId($orderId);
        $formFieldBuilder->addOrderReference($this->helper->getPmtReferenceNumber($order->getIncrementId() + 100));
        $formFieldBuilder->addPaymentId($pmt_id);
        $formFieldBuilder->addSellerId($this->sellerId);
        $formFieldBuilder->addDueDate($dueDate);

        $formFieldBuilder->addCustomerEmail($order->getCustomerEmail() ? $order->getCustomerEmail() : 'empty@email.com');
        $formFieldBuilder->addKeyVersion($this->keyVersion);

        $formFieldBuilder->addCallbackUrls(
            $this->_urlBuilder->getUrl('masterpass/authorize/success'),
            $this->_urlBuilder->getUrl('masterpass/authorize/error'),
            $this->_urlBuilder->getUrl('masterpass/authorize/cancel'),
            $this->_urlBuilder->getUrl('masterpass/authorize/delayed')
        );


        return $formFieldBuilder;
    }

    public function statusQuery($data = array())
    {
        $pmt_id = $this->helper->getSerializedMaksuturvaPaymentId($this->getQuote()->getPayment());

        $requestFields = [
            "pmtq_action" => "PAYMENT_STATUS_QUERY",
            "pmtq_version" => "0005",
            "pmtq_sellerid" => $this->sellerId,
            "pmtq_id" => $pmt_id,
            "pmtq_resptype" => "XML",
            "pmtq_hashversion" => "",
            "pmtq_hash" => "",
            "pmtq_keygeneration" => "001"
        ];


        $responseXml = $this->basicAuthPost($this->getStatusQueryUrl(), $requestFields, true);

        if (! $this->validateStatusQueryResponse($requestFields, $responseXml, $invalidField)) {
            throw new \Exception('Missing field in status query response: {$invalidField}');
        }

        return $responseXml;
    }

    protected function validateStatusQueryResponse($requestFields, $xml, &$invalidField)
    {
        $checkExists = array(
            "pmtq_buyeraddress1", "pmtq_buyercity", "pmtq_buyercountry", "pmtq_buyername",
            "pmtq_buyerpostalcode", "pmtq_certification", "pmtq_deliveryaddress1", "pmtq_deliverycity",
            "pmtq_deliverycountry", "pmtq_deliveryname", "pmtq_deliverypostalcode", "pmtq_escrow",
            "pmtq_externalcode1", "pmtq_externaltext", /*"pmtq_paymentdate",*/ "pmtq_paymentstarttimestamp",
            "pmtq_returncode", "pmtq_returntext"
        );

        foreach ($checkExists as $field) {
            if (! $xml->{$field}) {
                $invalidField = $field;
                return false;
            }
        }

        $checkEquals = array(
            'pmtq_action', 'pmtq_id', 'pmtq_sellerid', 'pmtq_version'
        );

        foreach ($checkEquals as $field) {
            if ($requestFields[$field] != $xml->{$field}) {
                $invalidField = $field;
                return false;
            }
        }
        return true;
    }

    /**
     * @param $data
     * @param bool $parseXml
     *
     * @return SimpleXMLElement|Zend_Http_Response
     */
    public function paymentPost($data, $parseXml = false)
    {
        return  $this->basicAuthPost($this->getPaymentRequestUrl(), $data, $parseXml);
    }

    protected function basicAuthPost($url, $data, $parseXml = false)
    {
        $client = new \Zend_Http_Client($url);
        try{
            $client->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'accept-encoding' => 'gzip, deflate',
                'accept-language' => 'en-US,en;q=0.8'
            ]);

            $client->setAuth($this->sellerId, $this->secretKey);
            $client->setParameterPost($data);
            $client->setMethod(\Zend_Http_Client::POST);

            $response = $client->request();

        }catch (\Exception $exception){
            throw $exception;
        }

        if (true === $parseXml) {
            $xmlString = $response->getBody();
            if (! ($response = simplexml_load_string($xmlString))) {
                throw new \Exception(__('Unkow masterpass gateway error'));
            }
        }
        return $response;
    }

    protected function getMaksuturvaPmtId($order, $payment)
    {
        $additional_data = $this->helper->getPaymentAdditionData($payment);
        if (isset($additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID])) {
            $pmt_id = $additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];
        } else {
            $pmt_id = $this->helper->generatePaymentId();
            $additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] = $pmt_id;
            $payment->setAdditionalData($this->helper->getSerializer()->serialize($additional_data));
            $payment->setMaksuturvaPmtId($pmt_id);
            $payment->save();
        }

        if ($order instanceof \Magento\Sales\Model\Order) {
            if (! $payment->getMaksuturvaPmtId()) {
                $payment->setMaksuturvaPmtId($pmt_id);
                $payment->save();
            }
        }
        return $pmt_id;
    }

    public function getOrderId($order)
    {
        if ($order instanceof \Magento\Quote\Model\Quote) {
            $orderId = $order->getReservedOrderId();
        } else {
            $orderId = $order->getIncrementId();
        }
        return $orderId;
    }
}