<?php
namespace Piimega\Maksuturva\Model\Gateway;

class Implementation extends \Piimega\Maksuturva\Model\Gateway\Base
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

    function __construct(
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Tax\Model\Calculation $calculationModel,
        \Piimega\Maksuturva\Api\MaksuturvaFormInterface $maksuturvaForm
    )
    {
        parent::__construct();
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
    }

    public  function setConfig($config)
    {
        $this->sellerId = $config['sellerId'];
        $this->secretKey = $config['secretKey'];
        $this->commUrl = $config['commurl'];
        $this->commEncoding = $config['commencoding'];
        $this->paymentDue = $config['paymentdue'];
        $this->keyVersion = $config['keyversion'];
        //some branch of Maksuturva doesn't have preselect_payment_method, such as masterpass
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
                throw new \Exception("order not found");
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
                $productDescription = $item->getProduct()->getShortDescription() ? $item->getProduct()->getShortDescription() : "SKU: " . $item->getSku();

                $sku = $item->getSku();
                if (mb_strlen($sku) > 10) {
                    $sku = mb_substr($sku, 0, 10);
                }

                $row = array(
                    'pmt_row_name' => $productName,
                    'pmt_row_desc' => $productDescription,
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
                        \error_log("Maksuturva module FAIL: more than one children for configurable product!");
                        continue;
                    }

                    if (in_array($items[$itemId + 1], $children) == false) {
                        \error_log("Maksuturva module FAIL: No children in order!");
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
                    if (strlen($child->getProduct()->getShortDescription()) > 0) {
                        $row['pmt_row_desc'] = $child->getProduct()->getShortDescription();
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

            $shippingTaxRate = floatval($this->_calculationModel->getRate($request));

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

            $options = array();
            $options["pmt_keygeneration"] = $this->keyVersion;


            // store unique transaction id on payment object for later retrieval
            //$this->getPayment() as same as $order->getPayment() in this case
            $payment = $this->getPayment();
            $additional_data = $payment->getAdditionalData();
            if(is_string($additional_data)){
                $additional_data = $this->helper->getSerializer()->unserialize($additional_data);
            }
            if (isset($additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID])) {
                $pmt_id = $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];
            } else {
                $pmt_id = $this->helper->generatePaymentId();
                $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] = $pmt_id;
                $payment->setAdditionalData($this->helper->getSerializer()->serialize($additional_data));
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

            $this->helper->maksuturvaLogger(var_export($options, true), null, 'maksuturva.log', true);
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
            "request_locale" => $this->_scopeConfig->getValue('maksuturva_payment/maksuturva_config/locale'), // allowed values: fi, sv, en
            "totalamount" => number_format($total, 2, ",", ""),
        );

        try {
            $response = $this->getPostResponse($this->getPaymentMethodsUrl(), $fields, 5);
        } catch (\Piimega\Maksuturva\Model\Gateway\Exception $e) {

            return false;
        }

        $xml = simplexml_load_string($response);
        $obj = json_decode(json_encode($xml));

        if (property_exists($obj, 'paymentmethod') && $obj->paymentmethod) {
            return $obj->paymentmethod;
        } else {
            return false;
        }
    }

    public function getPaymentMethodsUrl()
    {
        return $this->commUrl . \Piimega\Maksuturva\Model\Gateway\Base::PAYMENT_METHOD_URN;
    }

    public function getPaymentRequestUrl()
    {
        return $this->commUrl . \Piimega\Maksuturva\Model\Gateway\Base::PAYMENT_SERVICE_URN;
    }

    public function getStatusQueryUrl()
    {
        return $this->commUrl . \Piimega\Maksuturva\Model\Gateway\Base::PAYMENT_STATUS_QUERY_URN;
    }

    public function getAddDeliveryInfoUrl()
    {
        return $this->commUrl . \Piimega\Maksuturva\Model\Gateway\Base::PAYMENT_ADD_DELIVERYINFO_URN;
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
        $payment = $this->getPayment();
        $additional_data = $payment->getAdditionalData();
        $pmt_id = $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];


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

        // overrides with user-defined fields
        $this->_statusQueryData = array_merge($defaultFields, $data);

        // hash calculation
        $hashFields = array(
            "pmtq_action",
            "pmtq_version",
            "pmtq_sellerid",
            "pmtq_id"
        );
        $hashString = '';
        foreach ($hashFields as $hashField) {
            $hashString .= $this->_statusQueryData[$hashField] . '&';
        }
        $hashString .= $this->secretKey . '&';
        // last step: the hash is placed correctly
        $this->_statusQueryData["pmtq_hash"] = strtoupper(hash($this->_hashAlgoDefined, $hashString));

        $res = $this->getPostResponse($this->getStatusQueryUrl(), $this->_statusQueryData);

        // we will not rely on xml parsing - instead, the fields are going to be collected by means of preg_match
        $parsedResponse = array();
        $responseFields = array(
            "pmtq_action", "pmtq_version", "pmtq_sellerid", "pmtq_id",
            "pmtq_amount", "pmtq_returncode", "pmtq_returntext", "pmtq_trackingcodes",
            "pmtq_sellercosts", "pmtq_paymentmethod", "pmtq_escrow", "pmtq_certification", "pmtq_paymentdate",
            "pmtq_buyername", "pmtq_buyeraddress1", "pmtq_buyeraddress2",
            "pmtq_buyerpostalcode", "pmtq_buyercity", "pmtq_hash"
        );
        foreach ($responseFields as $responseField) {
            preg_match("/<$responseField>(.*)?<\/$responseField>/i", $res, $match);
            if (count($match) == 2) {
                $parsedResponse[$responseField] = $match[1];
            }
        }

        // do not provide a response which is not valid
        if (!$this->_verifyStatusQueryResponse($parsedResponse)) {
            throw new \Piimega\Maksuturva\Model\Gateway\Exception(array("The authenticity of the answer could't be verified. Hashes didn't match."), self::EXCEPTION_CODE_HASHES_DONT_MATCH);
        }

        // return the response - verified
        return $parsedResponse;
    }

    public function processStatusQueryResult($response)
    {
        $order = $this->getOrder();
        $result = array('success' => 'error', 'message' => '');

        switch ($response["pmtq_returncode"]) {
            // set as paid if not already set
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID_DELIVERY:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_COMPENSATED:

                $isDelayedCapture = $this->_maksuturvaModel->isDelayedCaptureCase($response['pmtq_paymentmethod']);
                if ($isDelayedCapture) {
                    $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    if($this->getConfigData('paid_order_status')){
                        $processStatus = $this->getConfigData('paid_order_status');
                    }else{
                        $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    }
                    $order->setState($processState, true, __('Payment capture authorized by Maksuturva'));
                    $order->setStatus($processStatus, true, __('Payment capture authorized by Maksuturva'));
                    $order->save();

                    $result['message'] = __('Payment capture authorized by Maksuturva.');
                    $result['success'] = 'success';
                } else {
                    if ($order->hasInvoices() == false) {
                        if ($order->canInvoice() && $this->_scopeConfig->getValue('maksuturva_payment/maksuturva_config/generate_invoice')) {
                            $invoice = $order->prepareInvoice();
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                            //do capture in register step
                            $invoice->register();
                            $order->addRelatedObject($invoice);

                            $result['message'] = __('Payment confirmed by Maksuturva. Invoice saved.');
                            $result['success'] = 'success';

                            $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                            if($this->getConfigData('paid_order_status')){
                                $processStatus = $this->getConfigData('paid_order_status');
                            }else{
                                $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                            }
                            $order->setState($processState, true, __('Payment confirmed by Maksuturva'));
                            $order->setStatus($processStatus, true, __('Payment confirmed by Maksuturva'));
                            $order->save();
                        }
                    } else {
                        $result['message'] = __('Payment confirmed by Maksuturva. Invoices already exist');
                        $result['success'] = 'success';
                    }
                }

                break;

            // set payment cancellation with the notice
            // stored in response_text
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAYER_RECLAMATION:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_CANCELLED:
                //Mark the order cancelled
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, __('Payment canceled in Maksuturva'));
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED, true, __('Payment canceled in Maksuturva'));
                $order->save();
                $result['message'] = __('Payment canceled in Maksuturva');
                $result['success'] = "error";

                break;

            // no news for buyer and seller\Piimega\Maksuturva\Model\Gateway\Implementation
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_NOT_FOUND:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_FAILED:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_WAITING:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_UNPAID:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_UNPAID_DELIVERY:
            default:
                // no action here
                $result['message'] = __('No change, still awaiting payment');
                $result['success'] = "notice";
                break;
        }

        return $result;
    }

    public function addDeliveryInfo($payment)
    {
        $additional_data = $payment->getAdditionalData();
        $pkg_id = $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];

        $this->helper->maksuturvaLogger("Adding delivery info for pkg_id {$pkg_id}", null, 'maksuturva.log', true);

        $deliveryData = array(
            "pkg_version" => "0002",
            "pkg_sellerid" => $this->sellerId,
            "pkg_id" => $pkg_id,
            "pkg_deliverymethodid" => $payment->getOrder()->getShippingMethod(),
            "pkg_adddeliveryinfo" => "Deliveryed by ".$payment->getOrder()->getShippingMethod()."",
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
                return array('pkg_resultcode' => $resultCode, 'pkg_id' => (string)$xml->pkg_id, 'pkg_resulttext' => (string)$xml->pkg_resulttext);
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Error on Maksuturva pkg creation: %1", (string)$xml->pkg_resulttext)
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
        return $this->_scopeConfig->getValue('maksuturva_payment/maksuturva_config/'.$path.'', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }
}