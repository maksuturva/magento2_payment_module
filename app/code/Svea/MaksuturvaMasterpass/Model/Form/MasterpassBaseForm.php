<?php
namespace Svea\MaksuturvaMasterpass\Model\Form;
class MasterpassBaseForm extends \Magento\Framework\DataObject
{
    protected $items;

    protected $products_rows;

    /**
     * @var array API formatted fields
     */
    protected $fields;

    protected $discountAmount;
    protected $keyVersion;
    protected $paymentId;
    protected $orderId;
    protected $orderReference;
    protected $sellerId;
    protected $dueDate;
    protected $callbackUrls = [];
    protected $totalAmount;
    protected $totalSellerCosts;
    protected $customerEmail;
    protected $shippingCost;
    protected $discountDescription;
    protected $billingAddress;
    protected $shippingAddress;
    protected $shippingCostTax;
    protected $shippingCostDescription;

    protected $_taxHelper;
    protected $_storeManager;
    protected $_calculationModel;


    public function __construct(
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Model\Calculation $calculationModel
    )
    {
        $this->totalAmount = 0;
        $this->discountAmount = 0;
        $this->products_rows = array();
        $this->fields = array();

        $this->_taxHelper = $taxHelper;
        $this->_storeManager = $storeManager;
        $this->_calculationModel = $calculationModel;
    }

    public function build()
    {
        foreach ($this->items as $itemId => $item) {
            $this->_buildOrderItem($itemId, $item);
        }

        if ($this->discountAmount != 0) {
            $this->_buildDiscountItem();
        }

        $this->_buildAddresses();

        $this->fields["pmt_keygeneration"] = $this->keyVersion;
        $this->fields["pmt_id"] = $this->paymentId;

        $this->fields["pmt_orderid"] = $this->orderId;
        $this->fields["pmt_reference"] = $this->orderReference;
        $this->fields["pmt_sellerid"] = $this->sellerId;
        $this->fields["pmt_duedate"] = $this->dueDate;

        $this->fields["pmt_okreturn"] = $this->callbackUrls['success'];
        $this->fields["pmt_errorreturn"] = $this->callbackUrls['error'];
        $this->fields["pmt_cancelreturn"] = $this->callbackUrls['cancel'];
        $this->fields["pmt_delayedpayreturn"] = $this->callbackUrls['delayed'];

        $this->fields["pmt_amount"] = str_replace('.', ',', sprintf("%.2f", $this->totalAmount));

        // emaksut, deprecated feature
        $this->fields["pmt_escrow"] = "N";

        $this->fields["pmt_sellercosts"] = str_replace('.', ',', sprintf("%.2f", $this->totalSellerCosts));

        $this->fields["pmt_rows"] = count($this->products_rows);
        $this->fields["pmt_rows_data"] = $this->products_rows;

        $this->fields["pmt_keygeneration"] = $this->keyVersion;
        $this->fields["pmt_buyeremail"] = $this->customerEmail;
        
        return $this->fields;
    }

    public function addItems(array $items)
    {
        $this->items = $items;
    }

    public function addOrderId($orderId){
        $this->orderId = $orderId;
    }

    public function addOrderReference($orderReference)
    {
        $this->orderReference = $orderReference;
    }

    public function addPaymentId($paymentId){
        $this->paymentId = $paymentId;
    }

    public function addDueDate($dueDate){
        $this->dueDate = $dueDate;
    }

    public function addSellerId($sellerId){
        $this->sellerId = $sellerId;
    }

    public function addKeyVersion($keyVersion){
        $this->keyVersion = $keyVersion;
    }

    public function addCustomerEmail($email){
        $this->customerEmail = $email;
    }

    public function addCallbackUrls($success, $error, $cancel, $delayed)
    {
        $this->callbackUrls = [
            'success' => $success,
            'error' => $error,
            'cancel' => $cancel,
            'delayed' => $delayed
        ];
    }

    public function addShippingCost($cost, $taxAmount, $description)
    {
        $this->shippingCost = $cost;
        $this->shippingCostTax = $taxAmount;
        $this->shippingCostDescription = $description;
    }

    public function setBillingAddress($address)
    {
        if ($address instanceof \Magento\Quote\Model\Quote\Address ||
            $address instanceof \Magento\Sales\Model\Order\Address) {
            $this->billingAddress = $address;
            return true;
        }
        return false;
    }

    public function setShippingAddress($address)
    {
        if ($address instanceof \Magento\Quote\Model\Quote\Address ||
            $address instanceof \Magento\Sales\Model\Order\Address) {
            $this->shippingAddress = $address;
            return true;
        }
        return false;
    }

    public function addDiscountItem($discount, $description)
    {
        $this->discountAmount = $discount;
        $this->discountDescription = $description;
    }

    protected function _buildOrderItem($itemId, $item)
    {
        $productName = $item->getName();
        $productDescription = $item->getProduct()->getShortDescription() ? $item->getProduct()->getShortDescription() : "SKU: " . $item->getSku();

        $sku = $item->getSku();
        if (mb_strlen($sku) > 10) {
            $sku = mb_substr($sku, 0, 10);
        }

        if ($item instanceof \Magento\Quote\Model\Quote\Item) {
            $itemQty = $item->getQty();
        } else {
            $itemQty = $item->getQtyOrdered();
        }

        $row = array(
            'pmt_row_name' => $productName,                                                        //alphanumeric        max lenght 40             -
            'pmt_row_desc' => $productDescription,                                                       //alphanumeric        max lenght 1000      min lenght 1
            'pmt_row_quantity' => str_replace('.', ',', sprintf("%.2f", $itemQty)),                                                     //numeric             max lenght 8         min lenght 1
            'pmt_row_articlenr' => $sku,
            'pmt_row_deliverydate' => date("d.m.Y"),                                                   //alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
            'pmt_row_price_net' => str_replace('.', ',', sprintf("%.2f", $item->getPrice())),          //alphanumeric        max lenght 17        min lenght 4         n,nn
            'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $item->getTaxPercent())),                  //alphanumeric        max lenght 5         min lenght 4         n,nn
            'pmt_row_discountpercentage' => "0,00",                                                    //alphanumeric        max lenght 5         min lenght 4         n,nn
            'pmt_row_type' => 1,
        );

        if ($item->getProductType() == 'configurable' && $item->getChildrenItems() != null && sizeof($item->getChildrenItems()) > 0) {
            $this->_buildConfigurableOrderItem($row, $itemId, $item);

        } elseif ($item->getParentItem() != null && $item->getParentItem()->getProductType() == 'configurable') {
            //CONFIGURABLE PRODUCT - CHILD
            //as child's information already copied to parent's row, no child row is displayed
            return;
        } elseif ($item->getProductType() == 'bundle' && $item->getChildrenItems() != null && sizeof($item->getChildrenItems()) > 0) {
            //BUNDLED PRODUCT - PARENT
            //bundled product parents won't be charged in invoice so unline other products, the quantity is fetched from qtyOrdered,
            //price will be nullified as the prices are available in children
            $this->_buildBundledProductParent($row, $item);

        } elseif ($item->getParentItem() != null && $item->getParentItem()->getProductType() == 'bundle') {
            //BUNDLED PRODUCT - CHILD
            //the quantity information with parent's quantity is added to child's description
            $this->_buildBundledProductChild($row, $item);
        } else {
            //SIMPLE OR GROUPED PRODUCT
            $this->totalAmount += $item->getPriceInclTax() * $itemQty;
        }
        array_push($this->products_rows, $row);
    }

    protected function _buildConfigurableOrderItem(&$row, $itemId, $item)
    {
        //CONFIGURABLE PRODUCT - PARENT
        //copies child's name, shortdescription and SKU as parent's

        $children = $item->getChildrenItems();

        if (sizeof($children) != 1) {
            error_log("Maksuturva module FAIL: more than one children for configurable product!");
            return;
        }

        if (in_array($this->items[$itemId + 1], $children) == false) {
            error_log("Maksuturva module FAIL: No children in quote!");
            return;
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
        $this->totalAmount += $item->getPriceInclTax() * $item->getQtyToInvoice();
        return $row;
    }

    protected function _buildBundledProductParent(&$row, $item)
    {
        $row['pmt_row_quantity'] = str_replace('.', ',', sprintf("%.2f", $item->getQtyOrdered()));
        if ($item->getProduct()->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            $row['pmt_row_price_net'] = str_replace('.', ',', sprintf("%.2f", '0'));
        } else {
            $this->totalAmount += $item->getPriceInclTax() * $item->getQtyOrdered();
        }
        $row['pmt_row_type'] = 4; //mark product as tailored product
        return $row;
    }

    protected function _buildBundledProductChild(&$row, $item)
    {
        $parentQty = $item->getParentItem()->getQtyOrdered();

        if (intval($parentQty, 10) == $parentQty) {
            $parentQty = intval($parentQty, 10);
        }

        $unitQty = $item->getQtyOrdered() / $parentQty;

        if (intval($unitQty, 10) == $unitQty) {
            $unitQty = intval($unitQty, 10);
        }

        $row['pmt_row_name'] = $unitQty . " X " . $parentQty . " X " . $item->getName();
        $row['pmt_row_quantity'] = str_replace('.', ',', sprintf("%.2f", $item->getQty()));
        $this->totalAmount += $item->getPriceInclTax() * $item->getQtyOrdered();
        $row['pmt_row_type'] = 4; //mark product as taloired product - by default not returnable
        return $row;
    }

    protected function _buildDiscountItem()
    {
        if ($this->discountAmount > ($this->shippingCost + $this->totalAmount)) {
            $this->discountAmount = ($this->shippingCost + $this->totalAmount);
        }
        $row = array(
            'pmt_row_name' => "Discount",
            'pmt_row_desc' => "Discount: " . $this->discountDescription,
            'pmt_row_quantity' => 1,
            'pmt_row_deliverydate' => date("d.m.Y"),
            'pmt_row_price_net' =>
                str_replace(
                    '.',
                    ',',
                    sprintf(
                        "%.2f",
                        $this->discountAmount
                    )
                ),
            'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", 0)),
            'pmt_row_discountpercentage' => "0,00",
            'pmt_row_type' => 6, // discounts
        );
        $this->totalAmount += $this->discountAmount;
        array_push($this->products_rows, $row);
    }

    protected function _buildShippingCost()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $taxId = $this->_taxHelper->getShippingTaxClass($storeId);
        $request = $this->_calculationModel->getRateRequest();
        $request->setCustomerClassId($this->_getCustomerTaxClass())
            ->setProductClassId($taxId);
        $shippingTaxRate = floatval($this->_calculationModel->getRate($request));

        $row = array(
            'pmt_row_name' => __('Shipping'),
            'pmt_row_desc' => $this->shippingCostDescription,
            'pmt_row_quantity' => 1,
            'pmt_row_deliverydate' => date("d.m.Y"),
            'pmt_row_price_net' => str_replace('.', ',', sprintf("%.2f", $this->shippingCost)),
            'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $shippingTaxRate)),
            'pmt_row_discountpercentage' => "0,00",
            'pmt_row_type' => 2,
        );
        $this->totalSellerCosts += $this->shippingCost + $this->shippingCostTax;
        array_push($this->products_rows, $row);
    }

    protected function _buildAddresses()
    {
        $this->fields["pmt_buyername"] = ($this->billingAddress ? $this->billingAddress->getName() : 'Empty field');
        $this->fields["pmt_buyeraddress"] = ($this->billingAddress ? implode(' ', $this->billingAddress->getStreet()) : 'Empty field');
        $this->fields["pmt_buyerpostalcode"] = ($this->billingAddress && $this->billingAddress->getPostcode() ? $this->billingAddress->getPostcode() : '000');
        $this->fields["pmt_buyercity"] = ($this->billingAddress ? $this->billingAddress->getCity() : 'Empty field');
        $this->fields["pmt_buyercountry"] = ($this->billingAddress ? $this->billingAddress->getCountryId() : 'fi');
        if ($this->billingAddress && $this->billingAddress->getTelephone()) {
            $this->fields["pmt_buyerphone"] = preg_replace('/[^\+\d\s\-\(\)]/', '', $this->billingAddress->getTelephone());
        }

        // Delivery information
        $this->fields["pmt_deliveryname"] = ($this->shippingAddress ? $this->shippingAddress->getName() : '');
        $this->fields["pmt_deliveryaddress"] = ($this->shippingAddress ? implode(' ', $this->shippingAddress->getStreet()) : '');
        $this->fields["pmt_deliverypostalcode"] = ($this->shippingAddress ? $this->shippingAddress->getPostcode() : '');
        $this->fields["pmt_deliverycity"] = ($this->shippingAddress ? $this->shippingAddress->getCity() : '');
        $this->fields["pmt_deliverycountry"] = ($this->shippingAddress ? $this->shippingAddress->getCountryId() : '');
    }
}