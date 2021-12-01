<?php
namespace Svea\Maksuturva\Model\Gateway;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Framework\Convert\Xml;
use Svea\Maksuturva\Model\Config\Config;

class Implementation extends \Svea\Maksuturva\Model\Gateway\Base
{
    const MAKSUTURVA_PKG_RESULT_SUCCESS = 00;
    const MAKSUTURVA_PKG_RESULT_PAYMENT_NOT_FOUND = 10;
    const MAKSUTURVA_PKG_RESULT_FAILED = 11;
    const MAKSUTURVA_PKG_RESULT_NO_SERVICE = 12;
    const MAKSUTURVA_PKG_RESULT_INVALID_FORMAT = 20;
    const MAKSUTURVA_PKG_RESULT_EXISTING_PKG = 30;
    const MAKSUTURVA_PKG_RESULT_PKG_NOT_FOUND = 31;
    const MAKSUTURVA_PKG_RESULT_INVALID_METHODID = 40;
    const MAKSUTURVA_PKG_RESULT_METHOD_NOT_ALLOWED = 41;
    const MAKSUTURVA_PKG_RESULT_FORCED_UPDATE_REQUIRED = 42;
    const MAKSUTURVA_PKG_RESULT_ERROR = 99;

    protected $sellerId = "";
    protected $order = null;
    protected $_currentOrder;
    protected $form = null;
    protected $preSelectPaymentMethod;
    protected $helper;
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $_groupFactory;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_taxHelper;
    protected $_calculationModel;
    protected $_maksuturvaForm;
    protected $_maksuturvaModel;
    protected $commUrl;
    protected $secretKey;
    protected $payment;
    protected $commEncoding;
    protected $keyVersion;
    protected $paymentDue;
    protected $eventManager;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curlClient;

    function __construct(
        \Svea\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $calculationModel,
        \Svea\Maksuturva\Api\MaksuturvaFormInterface $maksuturvaForm,
        \Magento\Framework\HTTP\Client\Curl $curl = null,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Xml $xmlConvert,
        Config $config
    )
    {
        parent::__construct($xmlConvert, $config);
        $this->helper = $maksuturvaHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_groupFactory = $groupFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_taxHelper = $taxHelper;
        $this->_calculationModel = $calculationModel;
        $this->_maksuturvaForm = $maksuturvaForm;
        $this->curlClient = $curl ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\HTTP\Client\Curl::class); 
        $this->eventManager = $eventManager ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Event\ManagerInterface::class);
        }

    public  function setConfig($config)
    {
        $this->sellerId = $config['sellerId'];
        $this->secretKey = $config['secretKey'];
        $this->commUrl = $config['commurl'];
        $this->commEncoding = $config['commencoding'];
        $this->paymentDue = $config['paymentdue'];
        $this->keyVersion = $config['keyversion'];
        if (isset($config['preselect_payment_method'])){
            $this->preSelectPaymentMethod = $config['preselect_payment_method'];
        }
        return $this;
    }

    public function getForm()
    {
        if (!$this->form) {

            $order = $this->getOrder();
            if(!($order instanceof \Magento\Sales\Model\Order)){
                throw new \Exception("Order not found");
            }

            $dueDate = date("d.m.Y", strtotime("+" . $this->paymentDue . " day"));

            $items = $order->getAllItems();
            $orderData = $order->getData();
            $totalAmount = 0;
            $totalSellerCosts = 0;

            $products_rows = array();
            foreach ($items as $itemId => $item) {
                $itemData = $item->getData();
                $productName = $item->getName();

                $sku = $item->getSku();
                if (mb_strlen($sku) > 10) {
                    $sku = mb_substr($sku, 0, 10);
                }

                $row = array(
                    'pmt_row_name' => $productName,
                    'pmt_row_desc' => $sku,
                    'pmt_row_quantity' => str_replace('.', ',', sprintf("%.2f", $item->getQtyToInvoice())),
                    'pmt_row_articlenr' => $sku,
                    'pmt_row_deliverydate' => date("d.m.Y"),
                    'pmt_row_price_net' => str_replace('.', ',', sprintf("%.2f", $item->getBasePrice())),
                    'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $itemData["tax_percent"])),
                    'pmt_row_discountpercentage' => "0,00",
                    'pmt_row_type' => 1,
                );

                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE && $item->getChildrenItems() != null && sizeof($item->getChildrenItems()) > 0) {
                    $children = $item->getChildrenItems();

                    if (sizeof($children) != 1) {
                        \error_log("Svea Payments module FAIL: more than one children for configurable product!");
                        continue;
                    }

                    if (in_array($items[$itemId + 1], $children) == false) {
                        \error_log("Svea Payments module FAIL: No children in order!");
                        continue;
                    }

                    $child = $children[0];
                    $row['pmt_row_name'] = $child->getName();
                    $childSku = $child->getSku();

                    if (strlen($childSku) > 0) {
                        if (mb_strlen($childSku) > 10) {
                            $childSku = mb_substr($childSku, 0, 10);
                        }

                        $row['pmt_row_articlenr'] = $childSku;
                    }
                    if (strlen($childSku) > 0) {
                        $row['pmt_row_desc'] = $childSku;
                    }
                    $totalAmount += $itemData["base_price_incl_tax"] * $item->getQtyToInvoice();

                }

                else if ($item->getParentItem() != null && $item->getParentItem()->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    continue;
                }

                else if ($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE && $item->getChildrenItems() != null && sizeof($item->getChildrenItems()) > 0) {
                    $row['pmt_row_quantity'] = str_replace('.', ',', sprintf("%.2f", $item->getQtyOrdered()));
                    if ($item->getProduct()->getPriceType() == 0) { //if price is fully dynamic
                        $row['pmt_row_price_net'] = str_replace('.', ',', sprintf("%.2f", '0'));
                    } else {
                        $totalAmount += $itemData["price_incl_tax"] * $item->getQtyOrdered();
                    }
                    $row['pmt_row_type'] = 4;
                }

                else if ($item->getParentItem() != null && $item->getParentItem()->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                    $parentQty = $item->getParentItem()->getQtyOrdered();

                    if (intval($parentQty, 10) == $parentQty) {
                        $parentQty = intval($parentQty, 10);
                    }

                    $unitQty = $item->getQtyOrdered() / $parentQty;

                    if (intval($unitQty, 10) == $unitQty) {
                        $unitQty = intval($unitQty, 10);
                    }

                    $row['pmt_row_name'] = $unitQty . " X " . $parentQty . " X " . $item->getName();
                    $row['pmt_row_quantity'] = str_replace('.', ',', sprintf("%.2f", $item->getQtyOrdered()));
                    $totalAmount += $itemData["base_price_incl_tax"] * $item->getQtyOrdered();
                    $row['pmt_row_type'] = 4;

                }
                else {
                    $totalAmount += $itemData["base_price_incl_tax"] * $item->getQtyToInvoice();
                }
                array_push($products_rows, $row);
            }

            // row type 6
            $discount = 0;
            if (isset($orderData["base_discount_amount"]) && $orderData["base_discount_amount"] != 0) {
                $discount = $orderData["base_discount_amount"];
                if ($discount > ($orderData["base_shipping_amount"] + $totalAmount)) {
                    $discount = ($orderData["base_shipping_amount"] + $totalAmount);
                }
                $row = array(
                    'pmt_row_name' => "Discount",
                    'pmt_row_desc' => "Discount: " . $orderData["discount_description"],
                    'pmt_row_quantity' => 1,
                    'pmt_row_deliverydate' => date("d.m.Y"),
                    'pmt_row_price_net' =>
                        str_replace(
                            '.',
                            ',',
                            sprintf(
                                "%.2f",
                                $discount
                            )
                        ),
                    'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", 0)),
                    'pmt_row_discountpercentage' => "0,00",
                    'pmt_row_type' => 6, // discounts
                );
                array_push($products_rows, $row);
            }
            $totalAmount += $discount;

            $shippingDescription = ($order->getShippingDescription() ? $order->getShippingDescription() : 'Free Shipping');

            if(isset($orderData["base_shipping_amount"])){
                $shippingCost = $orderData["base_shipping_amount"];
            }else{
                $shippingCost = 0;
            }


            $taxId = $this->_taxHelper->getShippingTaxClass($this->_storeManager->getStore()->getId());
            $request = $this->_calculationModel->getRateRequest();
            $request->setCustomerClassId($this->_getCustomerTaxClass())
                ->setProductClassId($taxId);

            if(isset($orderData["base_shipping_tax_amount"])){
                $shippingTax = $orderData["base_shipping_tax_amount"];
            }else{
                $shippingTax = 0;
            }

            $shippingTaxRate = $this->getShippingTaxRate($shippingTax, $shippingCost);

            $row = array(
                'pmt_row_name' => __('Shipping'),
                'pmt_row_desc' => $shippingDescription,
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date("d.m.Y"),
                'pmt_row_price_net' => str_replace('.', ',', sprintf("%.2f", $shippingCost)),
                'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $shippingTaxRate)),
                'pmt_row_discountpercentage' => "0,00",
                'pmt_row_type' => 2,
            );
            $totalSellerCosts += $shippingCost + $shippingTax;
            array_push($products_rows, $row);

            $handlingFee = $order->getHandlingFee();
            //$this->helper->sveaLoggerDebug("Handling fee " . $handlingFee);            

            //Row type 3
            $row = [
                'pmt_row_name' => \__('Handling Fee'),
                'pmt_row_desc' => \__('Handling fee'),
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date("d.m.Y"),
                'pmt_row_price_net' => str_replace('.', ',', sprintf("%.2f", $handlingFee)),
                'pmt_row_vat' => "0,00",
                'pmt_row_discountpercentage' => "0,00",
                'pmt_row_type' => 3
            ];
            $totalSellerCosts += $handlingFee;
            $products_rows[] = $row;

            $options = array();
            $options["pmt_keygeneration"] = $this->keyVersion;

            // store unique transaction id on payment object for later retrieval
            //$this->getPayment() as same as $order->getPayment() in this case
            $payment = $this->getPayment();
            $additional_data = $payment->getAdditionalData();
            if(is_string($additional_data)){
                $additional_data = $this->helper->getSerializer()->unserialize($additional_data);
            }
            if (isset($additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID])) {
                $pmt_id = $additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];
            } else {
                $pmt_id = $this->helper->generatePaymentId();
                $additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] = $pmt_id;
                $payment->setAdditionalData($this->helper->getSerializer()->serialize($additional_data));
                $payment->setMaksuturvaPmtId($pmt_id);
                $payment->save();
            }
            $refernceNumber = $this->helper->getPmtReferenceNumber($order->getIncrementId() + 100);

            if($refernceNumber){
                $order->setOrderReferenceNumber($refernceNumber);
                $order->save();
            }

            $options["pmt_id"] = $pmt_id;
            $options["pmt_orderid"] = $order->getIncrementId();
            $options["pmt_reference"] = (string)$refernceNumber;
            $options["pmt_sellerid"] = $this->sellerId;
            $options["pmt_duedate"] = $dueDate;

            $options["pmt_okreturn"] = $this->_urlBuilder->getUrl('maksuturva/index/success');
            $options["pmt_errorreturn"] = $this->_urlBuilder->getUrl('maksuturva/index/error');
            $options["pmt_cancelreturn"] = $this->_urlBuilder->getUrl('maksuturva/index/cancel');
            $options["pmt_delayedpayreturn"] = $this->_urlBuilder->getUrl('maksuturva/index/delayed');

            $options["pmt_amount"] = str_replace('.', ',', sprintf("%.2f", $totalAmount));
            if ($this->getPreselectedMethod()) {
                $options["pmt_paymentmethod"] = $this->getPreselectedMethod();
            }

            // Customer Information
            $options["pmt_buyername"] = ($order->getBillingAddress() ? $order->getBillingAddress()->getName() : 'Empty field');
            $options["pmt_buyeraddress"] = ($order->getBillingAddress() ? implode(' ', $order->getBillingAddress()->getStreet()) : 'Empty field');
            $options["pmt_buyerpostalcode"] = ($order->getBillingAddress() && $order->getBillingAddress()->getPostcode() ? $order->getBillingAddress()->getPostcode() : '000');
            $options["pmt_buyercity"] = ($order->getBillingAddress() ? $order->getBillingAddress()->getCity() : 'Empty field');
            $options["pmt_buyercountry"] = ($order->getBillingAddress() ? $order->getBillingAddress()->getCountryId() : 'fi');
            if ($order->getBillingAddress()->getTelephone()) {
                $options["pmt_buyerphone"] = preg_replace('/[^\+\d\s\-\(\)]/', '', $order->getBillingAddress()->getTelephone());
            }
            $options["pmt_buyeremail"] = ($order->getCustomerEmail() ? $order->getCustomerEmail() : 'empty@email.com');

            // emaksut, deprecated feature
            $options["pmt_escrow"] = "N";

            // Delivery information
            $options["pmt_deliveryname"] = ($order->getShippingAddress() ? $order->getShippingAddress()->getName() : '');
            $options["pmt_deliveryaddress"] = ($order->getShippingAddress() ? implode(' ', $order->getShippingAddress()->getStreet()) : '');
            $options["pmt_deliverypostalcode"] = ($order->getShippingAddress() ? $order->getShippingAddress()->getPostcode() : '');
            $options["pmt_deliverycity"] = ($order->getShippingAddress() ? $order->getShippingAddress()->getCity() : '');
            $options["pmt_deliverycountry"] = ($order->getShippingAddress() ? $order->getShippingAddress()->getCountry() : '');

            $options["pmt_sellercosts"] = str_replace('.', ',', sprintf("%.2f", $totalSellerCosts));

            $options["pmt_rows"] = count($products_rows);
            $options["pmt_rows_data"] = $products_rows;

            $transportObject = new \Magento\Framework\DataObject(array('order' => $order, 'options' => $options));
            $this->eventManager->dispatch(
                'maksuturva_gateway_implementation_get_form_after',
                array('transport_object' => $transportObject)
            );
            $options = $transportObject->getOptions();
            
            $this->form = $this->_maksuturvaForm->setConfig(array('secretkey' => $this->secretKey, 'options' => $options, 'encoding' => $this->commEncoding, 'url' => $this->commUrl));
        }

        return $this->form;
    }

    public function getFormFields()
    {
        return $this->getForm()->getFieldArray();
    }

    public function getHashAlgo()
    {
        return $this->_hashAlgoDefined;
    }

    public function getPaymentMethods($total)
    {
        $fields = array(
            "sellerid" => $this->sellerId,
            "request_locale" => $this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/locale'), // allowed values: fi, sv, en
            "totalamount" => number_format($total, 2, ",", ""),
        );

        try {
            $response = $this->getPostResponse($this->getPaymentMethodsUrl(), $fields, 5);
        } catch (\Svea\Maksuturva\Model\Gateway\Exception $e) {
            return false;
        }

        $xml = simplexml_load_string($response);
        $obj = json_decode(json_encode($xml));

        
                
        if (property_exists($obj, 'paymentmethod') && $obj->paymentmethod) {
            /*if (is_array($obj->paymentmethod))
            $this->helper->sveaLoggerDebug("" . count($obj->paymentmethod) . " available payment methods for total " . $total);
            */
            return $obj->paymentmethod;
        } else {
            return false;
        }
    }

    public function getPaymentMethodsUrl()
    {
        return $this->commUrl . \Svea\Maksuturva\Model\Gateway\Base::PAYMENT_METHOD_URN;
    }

    public function getPaymentRequestUrl()
    {
        return $this->commUrl . \Svea\Maksuturva\Model\Gateway\Base::PAYMENT_SERVICE_URN;
    }

    public function getStatusQueryUrl()
    {
        return $this->commUrl . \Svea\Maksuturva\Model\Gateway\Base::PAYMENT_STATUS_QUERY_URN;
    }

    public function getAddDeliveryInfoUrl()
    {
        return $this->commUrl . \Svea\Maksuturva\Model\Gateway\Base::PAYMENT_ADD_DELIVERYINFO_URN;
    }

    /**
     * @return string
     */
    public function getPaymentCancelUrl()
    {
        return $this->commUrl . 'PaymentCancel.pmt';
    }

    protected function getPreselectedMethod()
    {
        if ($this->preSelectPaymentMethod) {
            return $this->getPayment()->getData('additional_information')['maksuturva_preselected_payment_method'];
        } else {
            return "";
        }
    }

    public function statusQuery($data = array())
    {
        /**
         * skip status query for sandbox testiasiakas
         */
        if ($this->getConfigData('sandboxmode')==1) {
            $this->helper->sveaLoggerInfo("Status query skipped because sandbox mode is activated.");
            return;
        }

        $payment = $this->getPayment();
        $additional_data = !is_array($payment->getAdditionalData())
            ? $this->helper->getSerializer()->unserialize($payment->getAdditionalData())
            : $payment->getAdditionalData();
        $pmt_id = $additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];

        $defaultFields = array(
            "pmtq_action" => "PAYMENT_STATUS_QUERY",
            "pmtq_version" => "0005",
            "pmtq_sellerid" => $this->sellerId,
            "pmtq_id" => $pmt_id,
            "pmtq_resptype" => "XML",
            //"pmtq_return" => "",	// optional
            "pmtq_hashversion" => $this->_pmt_hashversion,
            "pmtq_keygeneration" => $this->keyVersion
        );

        $statusQueryData = array_merge($defaultFields, $data);

        $this->curlClient->setCredentials($this->sellerId, $this->secretKey);

        try {
            $this->curlClient->setOptions([
                CURLOPT_HEADER => 0,
                CURLOPT_FRESH_CONNECT => 1,
                CURLOPT_FORBID_REUSE => 1,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_CONNECTTIMEOUT => 30,
            ]);

            $this->curlClient->post($this->getStatusQueryUrl(), $statusQueryData);
            if ($this->curlClient->getStatus() != 200) {
                $this->helper->sveaLoggerError("Status query failed for payment " . $pmt_id . " result, http responsecode=" . $this->curlClient->getStatus());
                throw new \Svea\Maksuturva\Model\Gateway\Exception(
                    ["Failed to communicate with Svea Payments. Please check the network connection. URL: " . $this->getStatusQueryUrl()
                    . " HTTP response code " . $this->curlClient->getStatus()]
                );
            }
        } catch (\Exception $e) {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(
                ["Failed to communicate with Svea Payments. Please check the network connection. URL: " . $this->getStatusQueryUrl() . " Exception: " . $e->getMessage()]
            );
        }

        $body = $this->curlClient->getBody();
        //$this->helper->sveaLoggerDebug("Status query HTTP response body " . print_r($body, true));               

        // we will not rely on xml parsing - instead, the fields are going to be collected by means of preg_match
        $parsedResponse = array();
        $responseFields = array(
            "pmtq_action", "pmtq_version", "pmtq_sellerid", "pmtq_id", "pmtq_orderid",
            "pmtq_amount", "pmtq_returncode", "pmtq_returntext", "pmtq_trackingcodes",
            "pmtq_sellercosts", "pmtq_invoicingfee", "pmtq_paymentmethod", "pmtq_escrow", 
            "pmtq_certification", "pmtq_paymentdate",
            "pmtq_buyername", "pmtq_buyeraddress1", "pmtq_buyeraddress2",
            "pmtq_buyerpostalcode", "pmtq_buyercity", "pmtq_hash"
        );
   
        foreach ($responseFields as $responseField) {
            preg_match("/<$responseField>(.*)?<\/$responseField>/i", $body, $match);
            if (count($match) == 2) {
                $parsedResponse[$responseField] = $match[1];
            }
        }

        if (!$this->_verifyStatusQueryResponse($parsedResponse)) {
            $this->helper->sveaLoggerError("Payment status query response verification failed.");
            throw new \Svea\Maksuturva\Model\Gateway\Exception(
                ["The authenticity of the answer could't be verified."],
                self::EXCEPTION_CODE_HASHES_DONT_MATCH
            );
        }

        $this->helper->sveaLoggerInfo("Payment status query for payment " . $pmt_id . " was successful.");

        return $parsedResponse;
    }

    public function processStatusQueryResult($response)
    {
        $order = $this->getOrder();
        $incrementid = $order->getIncrementId();
        $result = array('success' => 'error', 'message' => '');

        if (empty($response["pmtq_orderid"])) {
            $result['message'] = __('Mandatory response field pmtq_orderid is missing.');
            $result['success'] = "error";
            $this->helper->sveaLoggerError("Mandatory response field pmtq_orderid is missing.");
            return $result;
        }

        /* before processing the payment, check that response data matches order data */
        if ($response["pmtq_orderid"]!=$incrementid) {
            $result['message'] = __('Order id and status query response order id does not match! Status update failed.');
            $result['success'] = "error";
            $this->helper->sveaLoggerError("Order " . $incrementid . " does not match status query response orderid " . $response["pmtq_orderid"]);
            return $result;
        }
        $pmtq_amount = floatval(str_replace(',', '.', $response["pmtq_amount"]) );
        if ( empty($response["pmtq_sellercosts"]) )
            $pmtq_sellercosts = floatval(str_replace(',', '.', "0,00") );
        else
            $pmtq_sellercosts = floatval(str_replace(',', '.', $response["pmtq_sellercosts"]) );
        if ( empty($response["pmtq_invoicingfee"]) )
            $pmtq_invoicingfee = floatval(str_replace(',', '.', "0,00") );
        else
            $pmtq_invoicingfee = floatval(str_replace(',', '.', $response["pmtq_invoicingfee"]) );
        $total = floatval(str_replace(',', '.', $order->getGrandTotal() ) );

        // 5.00 is maximum accpted sum difference
        if ( abs($total - ($pmtq_amount+$pmtq_sellercosts-$pmtq_invoicingfee)) > 5.00) {
            $result['message'] = __('Order and status query response sum mismatch! Status update failed.');
            $result['success'] = "error";
            $this->helper->sveaLoggerError("Order " . $incrementid . " sum (amount " . $pmtq_amount . " + sellercosts " . 
                $pmtq_sellercosts .  " - invoicingfee " . $pmtq_invoicingfee . ") does not match order total " . $total);
            return $result;
        }

        switch ($response["pmtq_returncode"]) {
            // set as paid if not already set
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID_DELIVERY:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_COMPENSATED:
                $maksuturvaModel = $order->getPayment()->getMethodInstance();
                $isDelayedCapture = $maksuturvaModel->isDelayedCaptureCase($response['pmtq_paymentmethod']);
                if ($isDelayedCapture) {
                    $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    if($this->getConfigData('paid_order_status')){
                        $processStatus = $this->getConfigData('paid_order_status');
                    }else{
                        $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    }
                    $order->setState($processState, true, __('Payment capture authorized by Svea Payments'));
                    $order->setStatus($processStatus, true, __('Payment capture authorized by Svea Payments'));
                    $order->save();

                    $this->helper->sveaLoggerInfo("Order " . $incrementid . " status updated to 'Payment capture authorized by Svea Payments'");

                    $result['message'] = __('Payment capture authorized by Svea Payments.');
                    $result['success'] = 'success';
                } else {
                    if ($order->hasInvoices() == false) {
                        if ($order->canInvoice() && $this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/generate_invoice')) {
                            $invoice = $order->prepareInvoice();
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                            //do capture in register step
                            $invoice->register();
                            $order->addRelatedObject($invoice);

                            $result['message'] = __('Payment confirmed by Svea Payments. Invoice saved.');
                            $this->setOrderAsPaid($order);
                            $this->helper->sveaLoggerInfo("Order " . $incrementid . " status updated to 'Payment confirmed by Svea Payments.'");
                        }
                    } else {
                        $result['message'] = __('Payment confirmed by Svea Payments. Invoices already exist.');
                        /* resolve case when invoice exists but status in database is still pending payment */
                        if ($order->getStatus()==$this->getConfigData('order_status'))
                        {
                            $this->setOrderAsPaid($order);
                            $this->helper->sveaLoggerInfo("Order " . $incrementid . " status updated to 'Payment confirmed by Svea Payments.'");
                        }
                    }
                    $result['success'] = 'success';
                }
                break;

            // set payment cancellation with the notice
            // stored in response_text
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_RECLAMATION:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_CANCELLED:
                //Mark the order cancelled
                
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, __('Payment canceled in Svea Payments'));
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED, true, __('Payment canceled in Svea Payments'));
                $order->save();
                $result['message'] = __('Payment canceled in Svea Payments');
                $result['success'] = "error";
                $this->helper->sveaLoggerInfo("Order " . $incrementid . " status updated to 'Payment canceled in Svea Payments.'");
                break;

            // no news for buyer and seller\Svea\Maksuturva\Model\Gateway\Implementation
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_NOT_FOUND:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_FAILED:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_WAITING:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_UNPAID:
            case \Svea\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_UNPAID_DELIVERY:
            default:
                // no action here
                $result['message'] = __('No change, still awaiting payment');
                $result['success'] = "notice";
                $this->helper->sveaLoggerInfo("Order " . $incrementid . " status still waiting or unpaid.");
                break;
        }

        return $result;
    }

    private function setOrderAsPaid($order)
    {
        $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        if($this->getConfigData('paid_order_status')) {
            $processStatus = $this->getConfigData('paid_order_status');
        } else {
            $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
        }
        $order->setState($processState, true, __('Payment confirmed by Svea Payments'));
        $order->setStatus($processStatus, true, __('Payment confirmed by Svea Payments'));
        $order->save();
        $this->helper->sveaLoggerInfo("Order " . $order->getIncrementId() . " set as paid and status to '" . strval($processStatus) . "'");
    }
    
    public function addDeliveryInfo($payment)
    {
        try
        {                                                                                                                                                                                         
            $additional_data = $payment->getAdditionalData();
            $json_data = json_decode($additional_data, true);
            $pkg_id = $json_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];
            $this->helper->sveaLoggerInfo("Adding delivery info for pkg_id " . strval($pkg_id));                                                                        
        } catch (Exception $e)
        {                                                                                                                                                                                                                                                                                                                                                                                   
            $this->helper->sveaLoggerError("Add delivery function failed, parse exception " . $e->getMessage());
            return;
        }
        
        $deliveryData = array(
            "pkg_version" => "0002",
            "pkg_sellerid" => $this->sellerId,
            "pkg_id" => $pkg_id,
            "pkg_deliverymethodid" => $payment->getOrder()->getShippingMethod(),
            "pkg_adddeliveryinfo" => "Delivered by ".$payment->getOrder()->getShippingMethod()."",
            "pkg_allsent" => "Y",
            "pkg_resptype" => "XML",
            "pkg_hashversion" => $this->_pmt_hashversion,
            "pkg_keygeneration" => $this->keyVersion
        );

        // hash calculation
        $hashFields = array(
            "pkg_id",
            "pkg_deliverymethodid",
            "pkg_allsent",
        );
        $hashString = '';
        foreach ($hashFields as $hashField) {
            $hashString .= $deliveryData[$hashField] . '&';
        }
        $hashString .= $this->secretKey . '&';
        $deliveryData["pkg_hash"] = strtoupper(hash($this->_hashAlgoDefined, $hashString));

        $res = $this->getPostResponse($this->getAddDeliveryInfoUrl(), $deliveryData);

        $xml = new \Magento\Framework\Simplexml\Element($res);
        $resultCode = (string)$xml->pkg_resultcode;

        switch ($resultCode) {
            case self::MAKSUTURVA_PKG_RESULT_SUCCESS:
                $this->helper->sveaLoggerInfo("Delivery info for package id  " . strval($xml->pkg_id) . " result " . strval($xml->pkg_resulttext));
                return array('pkg_resultcode' => $resultCode, 'pkg_id' => (string)$xml->pkg_id, 'pkg_resulttext' => (string)$xml->pkg_resulttext);
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Error on Svea Payments pkg creation: %1", (string)$xml->pkg_resulttext)
                );
        }
    }

    protected function _getCustomerTaxClass()
    {
        $customerGroup = $this->getQuote()->getCustomerGroupId();
        if (!$customerGroup) {
            $customerGroup = $this->_scopeConfig->getValue('customer/create_account/default_group');
        }
        return $this->_groupFactory->create()->load($customerGroup)->getTaxClassId();
    }

    private function getShippingTaxRate($shippingTax, $shippingCost)
    {
        if ($shippingCost == 0) {
            return 0;
        }

        return floatval(round(($shippingTax / $shippingCost) * 100 * 2) / 2);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @throws \Exception
     * @return $this
     */
    public function changePaymentTransaction($payment, $amount)
    {
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var InvoiceInterface $invoice */
        $invoice = $payment->getCreditmemo()->getInvoice();
        
        if (abs($order->getBaseTotalInvoiced() - $order->getBaseTotalRefunded()) < .0001) {
            $cancelType = 'FULL_REFUND';
            $canRefundMore = false;
        } else {
            $cancelType = 'PARTIAL_REFUND';
            $canRefundMore = true;
        }

        $this->helper->sveaLoggerInfo("" . $order->getIncrementId() . " " . $cancelType . ". Details: invoiceGrandTotal=" . $invoice->getBaseGrandTotal()
             . ", invoiceTotalRefunded=" . $invoice->getBaseTotalRefunded() . ", orderBaseTotalInvoiced=" . $order->getBaseTotalInvoiced() 
             . ", orderTotalRefunded=" . $order->getBaseTotalRefunded() . ", amount=" . $amount);

        $parsedResponse = $this->cancel($payment, $amount, $cancelType);

        if ($parsedResponse['pmtc_returncode'] === self::PAYMENT_CANCEL_OK) {

            if($cancelType === 'FULL_REFUND') {
                $payment->setTransactionId($parsedResponse['pmtc_id'] . '-refund');
            } else {
                $id = time();
                $payment->setTransactionId($parsedResponse['pmtc_id'] . $id . '-refund');
            }

            $payment->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(!$canRefundMore);

        } elseif ($parsedResponse['pmtc_returncode'] === self::PAYMENT_CANCEL_ALREADY_SETTLED) {
            $this->refundAfterSettlement($payment, $amount);
        }

        return $this;
    }

    /**
     * Payment refund after settlement
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @throws \Exception
     */
    public function refundAfterSettlement($payment, $amount)
    {
        $this->canCancelSettled();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var InvoiceInterface $invoice */
        $invoice = $payment->getCreditmemo()->getInvoice();

        $canRefundMore = $invoice->canRefund();

        $parsedResponse = $this->cancel($payment, $amount, 'REFUND_AFTER_SETTLEMENT');

        if ($parsedResponse['pmtc_returncode'] === self::PAYMENT_CANCEL_OK) {

            $pay = [
                'payReference' => $parsedResponse['pmtc_pay_with_reference'],
                'payRecipientName' => $parsedResponse['pmtc_pay_with_recipientname'],
                'payAmount' => $parsedResponse['pmtc_pay_with_amount'],
                'payIban' => $parsedResponse['pmtc_pay_with_iban'],
            ];

            $msg = \__("Send refund money to") . PHP_EOL;
            $msg .= \__("Name: %s", $pay['payName']) . PHP_EOL;
            $msg .= \__("Iban: %s", $pay['payIban']) . PHP_EOL;
            $msg .= \__("Reference: %s", $pay['payReference']) . PHP_EOL;
            $msg .= \__("Amount: %s", $pay['payAmount']) . PHP_EOL;
            $msg .= \__("Maksuturva will refund the payer after receiving money.") . PHP_EOL;

            /** Add information to order & creditmemo for paying refund money toSvea Payments */
            $order->addStatusHistoryComment($msg);
            $payment->getCreditmemo()->addComment($msg);

            $id = time();
            $payment->setTransactionId($parsedResponse['pmtc_id'] . $id . '-refund');
            $payment->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(!$canRefundMore);

            $this->sendPaymentInformationEmail($order, $pay);
        }
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @param string $cancelType
     * @return mixed
     * @throws \Exception
     */
    private function cancel($payment, $amount, $cancelType)
    {
        $transactionId = $this->getTransactionId($payment);

        if (!$transactionId) {
            throw new \Exception('Can\'t refund online because transaction id is missing');
        }

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $fields = [
            'pmtc_action' => ($cancelType === 'REFUND_AFTER_SETTLEMENT') ? 'REFUND_AFTER_SETTLEMENT' : 'CANCEL',
            'pmtc_version' => '0005',
            'pmtc_sellerid' => $this->sellerId,
            'pmtc_id' => $transactionId,
            'pmtc_amount' => number_format($order->getGrandTotal(), 2, ',', ''),
            'pmtc_currency' => $order->getBaseCurrencyCode(),
            'pmtc_canceltype' => $cancelType,
            'pmtc_resptype' => 'XML',
            'pmtc_hashversion' => $this->_pmt_hashversion,
            'pmtc_keygeneration' => $this->keyVersion
        ];

        $hashFields = [
            'pmtc_action',
            'pmtc_version',
            'pmtc_sellerid',
            'pmtc_id',
            'pmtc_amount',
            'pmtc_currency',
            'pmtc_canceltype'
        ];

        /** If not full refund, add cancel amount */
        if ($fields['pmtc_canceltype'] !== 'FULL_REFUND'){
            $fields['pmtc_cancelamount'] = number_format($amount, 2, ',', '');
            $hashFields[] = 'pmtc_cancelamount';
        }

        $fields['pmtc_hash'] = $this->calculateHash($fields, $hashFields);
        $response = $this->getPostResponse($this->getPaymentCancelUrl(), $fields);
  
        return $this->processCancelPaymentResponse($response);
    }

    /**
     * @throws \Exception
     */
    public function canCancelSettled()
    {
        if (!$this->config->canCancelSettled()){
            throw new \Exception('Can\'t refund settled payments. Make sure module configurations are correct.');
        }

        return;
    }

    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    public function getOrder()
    {
        if ($this->_currentOrder == null)
        {
            $this->_currentOrder = $this->_orderFactory->create();
            $this->_currentOrder->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
        }
        return $this->_currentOrder;
    }

    public function setOrder(\Magento\Framework\Api\CustomAttributesDataInterface $order)
    {
        $this->_currentOrder = $order;
        return $this->_currentOrder;
    }

    public function getConfigData($path)
    {
        return $this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/'.$path.'', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPayment()
    {
        return $this->payment !== null ? $this->payment : $this->getOrder()->getPayment();
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }
}