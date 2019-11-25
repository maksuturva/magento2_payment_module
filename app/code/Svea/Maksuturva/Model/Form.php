<?php
namespace Svea\Maksuturva\Model;

class Form extends \Magento\Framework\Model\AbstractModel implements \Svea\Maksuturva\Api\MaksuturvaFormInterface
{
    protected $_errors;
    private $_originalFormData = array();
    protected $_formData = array();
    protected $_compulsoryData = array(
        'pmt_action',                    //alphanumeric        max lenght 50        min lenght 4        NEW_PAYMENT_EXTENDED
        'pmt_version',                   //alphanumeric        max lenght 4         min lenght 4        0004

        'pmt_sellerid',                  //alphanumeric        max lenght 15             -
        'pmt_id',                        //alphanumeric        max lenght 20             -
        'pmt_orderid',                   //alphanumeric        max lenght 50             -
        'pmt_reference',                 //numeric             max lenght 20        min lenght 4        Reference number + check digit
        'pmt_duedate',                   //alphanumeric        max lenght 10        min lenght 10       dd.MM.yyyy
        'pmt_amount',                    //alphanumeric        max lenght 17        min lenght 4
        'pmt_currency',                  //alphanumeric        max lenght 3         min lenght 3        EUR

        'pmt_okreturn',                  //alphanumeric        max lenght 200            -
        'pmt_errorreturn',               //alphanumeric        max lenght 200            -
        'pmt_cancelreturn',              //alphanumeric        max lenght 200            -
        'pmt_delayedpayreturn',          //alphanumeric        max lenght 200            -

        'pmt_escrow',                    //alpha               max lenght 1         min lenght 1         Maksuturva=Y, eMaksut=N
        'pmt_escrowchangeallowed',       //alpha               max lenght 1         min lenght 1         N

        'pmt_buyername',                 //alphanumeric        max lenght 40             -
        'pmt_buyeraddress',              //alphanumeric        max lenght 40             -
        'pmt_buyerpostalcode',           //numeric             max lenght 5              -
        'pmt_buyercity',                 //alphanumeric        max lenght 40             -
        'pmt_buyercountry',              //alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_deliveryname',              //alphanumeric        max lenght 40             -
        'pmt_deliveryaddress',           //alphanumeric        max lenght 40             -
        'pmt_deliverypostalcode',        //numeric             max lenght 5              -
        'pmt_deliverycountry',           //alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_sellercosts',               //alphanumeric        max lenght 17        min lenght 4         n,nn

        'pmt_rows',                      //numeric             max lenght 4         min lenght 1

        'pmt_charset',                   //alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_charsethttp',               //alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_hashversion',               //alphanumeric        max lenght 10             -               {SHA-512, SHA-256, SHA-1, MD5}
        /*'pmt_hash',                      //alphanumeric        max lenght 128       min lenght 32*/
        'pmt_keygeneration',             //numeric             max lenght 3              -
    );
    protected $_optionalData = array(
        'pmt_selleriban',
        'pmt_userlocale',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
        'pmt_buyeridentificationcode',
        'pmt_buyerphone',
        'pmt_buyeremail',
    );
    protected $_rowOptionalData = array(
        'pmt_row_articlenr',
        'pmt_row_unit',
    );

    protected $_rowCompulsoryData = array(
        'pmt_row_name',                  //alphanumeric        max lenght 40             -
        'pmt_row_desc',                  //alphanumeric        max lenght 1000      min lenght 1
        'pmt_row_quantity',              //numeric             max lenght 8         min lenght 1
        'pmt_row_deliverydate',          //alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
        'pmt_row_price_gross',           //alphanumeric        max lenght 17        min lenght 4         n,nn
        'pmt_row_price_net',             //alphanumeric        max lenght 17        min lenght 4         n,nn
        'pmt_row_vat',                   //alphanumeric        max lenght 5         min lenght 4         n,nn
        'pmt_row_discountpercentage',    //alphanumeric        max lenght 5         min lenght 4         n,nn
        'pmt_row_type',                  //numeric             max lenght 5         min lenght 1
    );

    private $_fieldLength = array(
        // min, max, required
        'pmt_action' => array(4, 50),
        'pmt_version' => array(4, 4),
        'pmt_sellerid' => array(1, 15),
        'pmt_selleriban' => array(18, 30), // optional
        'pmt_id' => array(1, 20),
        'pmt_orderid' => array(1, 50),
        'pmt_reference' => array(3, 20), // > 100
        'pmt_duedate' => array(10, 10),
        'pmt_userlocale' => array(5, 5), // optional
        'pmt_amount' => array(4, 17),
        'pmt_currency' => array(3, 3),
        'pmt_okreturn' => array(1, 200),
        'pmt_errorreturn' => array(1, 200),
        'pmt_cancelreturn' => array(1, 200),
        'pmt_delayedpayreturn' => array(1, 200),
        'pmt_escrow' => array(1, 1),
        'pmt_escrowchangeallowed' => array(1, 1),
        'pmt_invoicefromseller' => array(1, 1),    // opt
        'pmt_paymentmethod' => array(4, 4), // opt
        'pmt_buyeridentificationcode' => array(9, 11),    // opt
        'pmt_buyername' => array(1, 40),
        'pmt_buyeraddress' => array(1, 40),
        'pmt_buyerpostalcode' => array(1, 5),
        'pmt_buyercity' => array(1, 40),
        'pmt_buyercountry' => array(1, 2),
        'pmt_buyerphone' => array(0, 40),    // opt
        'pmt_buyeremail' => array(0, 40),    // opt
        'pmt_deliveryname' => array(1, 40),
        'pmt_deliveryaddress' => array(1, 40),
        'pmt_deliverypostalcode' => array(1, 5),
        'pmt_deliverycity' => array(1, 40),
        'pmt_deliverycountry' => array(1, 2),
        'pmt_sellercosts' => array(4, 17),
        'pmt_rows' => array(1, 4),
        'pmt_row_name' => array(1, 40),
        'pmt_row_desc' => array(1, 1000),
        'pmt_row_quantity' => array(1, 8),
        'pmt_row_deliverydate' => array(10, 10),
        'pmt_row_price_gross' => array(4, 17),
        'pmt_row_price_net' => array(4, 17),
        'pmt_row_vat' => array(4, 5),
        'pmt_row_discountpercentage' => array(4, 5),
        'pmt_row_type' => array(1, 5),
        'pmt_charset' => array(1, 15),
        'pmt_charsethttp' => array(1, 15),
        'pmt_hashversion' => array(1, 10),
        'pmt_keygeneration' => array(1, 3),
    );
    protected $_hashData = array(
        'pmt_action',
        'pmt_version',
        'pmt_selleriban',
        'pmt_id',
        'pmt_orderid',
        'pmt_reference',
        'pmt_duedate',
        'pmt_amount',
        'pmt_currency',
        'pmt_okreturn',
        'pmt_errorreturn',
        'pmt_cancelreturn',
        'pmt_delayedpayreturn',
        'pmt_escrow',
        'pmt_escrowchangeallowed',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
        'pmt_buyeridentificationcode',
        'pmt_buyername',
        'pmt_buyeraddress',
        'pmt_buyerpostalcode',
        'pmt_buyercity',
        'pmt_buyercountry',
        'pmt_deliveryname',
        'pmt_deliveryaddress',
        'pmt_deliverypostalcode',
        'pmt_deliverycity',
        'pmt_deliverycountry',
        'pmt_sellercosts',
        /*'pmt_row_* fields in specified order, one row at a time',
        '<merchant’s secret key >'*/
    );

    private $_secretKey;
    protected $_hashAlgoDefined = null;
    protected $_charset = 'UTF-8';
    protected $_charsethttp = 'UTF-8';

    public function setConfig($args)
    {
        if ($args['encoding']) {
            $this->_charset = $args['encoding'];
            $this->_charsethttp = $args['encoding'];
        }

        $data = $args['options'];

        $this->_secretKey = $args['secretkey'];

        $this->_formData['pmt_action'] = 'NEW_PAYMENT_EXTENDED';
        $this->_formData['pmt_version'] = '0004';
        $this->_formData['pmt_escrow'] = $data['pmt_escrow'];
        $this->_formData['pmt_keygeneration'] = '001';
        $this->_formData['pmt_currency'] = 'EUR';
        $this->_formData['pmt_escrowchangeallowed'] = 'N';

        $this->_formData['pmt_charset'] = $this->_charset;
        $this->_formData['pmt_charsethttp'] = $this->_charsethttp;

        // Force to cut off all amps and merge in current array_data
        foreach ($data as $key => $value) {
            if ($key == 'pmt_rows_data') {
                $rows = array();
                foreach ($value as $k => $v) {
                    $v = preg_replace('!\s+!', ' ', $v);
                    $rows[$k] = str_replace('&amp;', '', $v);
                }
                $this->_formData[$key] = $rows;
            } else {
                $value = preg_replace('!\s+!', ' ', $value);
                $this->_formData[$key] = str_replace('&amp;', '', $value);
            }
        }
        $hashAlgos = hash_algos();

        if (in_array("sha512", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-512';
            $this->_hashAlgoDefined = "sha512";
        } else if (in_array("sha256", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-256';
            $this->_hashAlgoDefined = "sha256";
        } else if (in_array("sha1", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-1';
            $this->_hashAlgoDefined = "sha1";
        } else if (in_array("md5", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'MD5';
            $this->_hashAlgoDefined = "md5";
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array('the hash algorithms SHA-512, SHA-256, SHA-1 and MD5 are not supported!'), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED);
        }
        return $this;
    }

    public function __get($name)
    {
        if (in_array($name, $this->_compulsoryData) || in_array($name, $this->_optionalData) || $name == 'pmt_rows_data') {
            return $this->_formData[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->_compulsoryData) || in_array($name, $this->_optionalData)) {
            $value = preg_replace('!\s+!', ' ', $value);
            $this->_formData[$name] = $value;
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Field $name is not part of the form"), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_FIELD_MISSING);
        }
    }

    protected function dataIsValid()
    {
        $isvalid = true;

        $DELIVERY_FIELDS = array(
            'pmt_deliveryname' => 'pmt_buyername',
            'pmt_deliveryaddress' => 'pmt_buyeraddress',
            'pmt_deliverypostalcode' => 'pmt_buyerpostalcode',
            'pmt_deliverycity' => 'pmt_buyercity',
            'pmt_deliverycountry' => 'pmt_buyercountry'
        );

        foreach ($DELIVERY_FIELDS as $dfield => $bfield) {
            if ((!isset($this->_formData[$dfield])) || mb_strlen(trim($this->_formData[$dfield])) == 0 || $this->_formData[$dfield] == NULL)
                $this->_formData[$dfield] = $this->_formData[$bfield];
        }

        foreach ($this->_compulsoryData as $compulsoryData) {
            if (array_key_exists($compulsoryData, $this->_formData)) {
                switch ($compulsoryData) {
                    case 'pmt_reference':
                        if (mb_strlen((string)$this->_formData['pmt_reference']) < 3) {
                            $isvalid = false;
                            $this->_errors[] = "$compulsoryData need to have at least 3 digits";
                        }
                        break;
                }
            } else {
                $isvalid = false;
                $this->_errors[] = "$compulsoryData is mandatory";
            }
        }

        if (array_key_exists("pmt_rows_data", $this->_formData)) {
            $countRows = 1;
            foreach ($this->_formData['pmt_rows_data'] as $rowData) {
                $isvalid = $this->itemIsValid($rowData, $countRows, $isvalid);
                $countRows++;
            }

        } else {
            $isvalid = false;
        }

        if (($countRows - 1) != $this->_formData['pmt_rows']) {
            $isvalid = false;
            $this->_errors[] = "The amount(" . $this->_formData['pmt_rows'] . ") of items passed in the field 'pmt_rows' don't match with real amount(" . ($countRows - 1) . ") of items";
        }

        $this->filterFieldsLength();

        return $isvalid;
    }

    protected function itemIsValid($data, $countRows = null, $isvalid = true)
    {
        foreach ($this->_rowCompulsoryData as $rowCompulsoryKeyData) {
            if (array_key_exists($rowCompulsoryKeyData, $data)) {
                switch ($rowCompulsoryKeyData) {
                    case 'pmt_row_price_gross':
                        if (array_key_exists('pmt_row_price_net', $data)) {
                            $isvalid = false;
                            $this->_errors[] = "pmt_row_price_net$countRows and pmt_row_price_gross$countRows are both supplied, when only one of them should be";
                        }
                        break;
                }
            } else {
                switch ($rowCompulsoryKeyData) {
                    case 'pmt_row_price_gross':
                        if (array_key_exists('pmt_row_price_net', $data)) {
                            break;
                        }
                    case 'pmt_row_price_net':
                        if (array_key_exists('pmt_row_price_gross', $data)) {
                            break;
                        }
                    default:
                        $isvalid = false;
                        $this->_errors[] = "$rowCompulsoryKeyData$countRows is mandatory";
                }

            }
        }

        return $isvalid;
    }

    public function getFieldArray()
    {
        if ($this->dataIsValid()) {
            $returnArray = array();

            foreach ($this->_formData as $key => $data) {
                if ($key == 'pmt_rows_data') {
                    $rowCount = 1;
                    foreach ($data as $rowData) {
                        foreach ($rowData as $rowKey => $rowInnerData) {
                            $returnArray[$this->convert_encoding($rowKey . $rowCount, $this->charsethttp)] = $this->convert_encoding($rowInnerData, $this->charsethttp);
                        }
                        $rowCount++;
                    }
                } else {
                    $returnArray[$this->convert_encoding($key, $this->charsethttp)] = $this->convert_encoding($data, $this->charsethttp);
                }
            }

            $returnArray[$this->convert_encoding('pmt_hash', $this->charsethttp)] = $this->convert_encoding($this->generateHash(), $this->_charset);

            return $returnArray;
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception($this->getErrors(), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS);
        }
    }

    public function setItemData($index, $dataKey, $value)
    {
        if (in_array($dataKey, $this->_rowCompulsoryData) || $dataKey($dataKey, $this->_rowOptionalData)) {
            $this->_formData['pmt_rows_data'][$index][$dataKey] = $value;
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Item field $dataKey is not part of the form"), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_FIELD_MISSING);
        }
    }

    public function getItemData($index, $dataKey)
    {
        if (in_array($dataKey, $this->_rowCompulsoryData) || $dataKey($dataKey, $this->_rowOptionalData)) {
            return $this->_formData['pmt_rows_data'][$index][$dataKey];
        }

        return null;
    }

    public function addItem($data)
    {
        if (!$this->itemIsValid($data)) {
            throw new \Svea\Maksuturva\Model\Gateway\Exception($this->getErrors(), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_INVALID_ITEM);
        }

        $this->_formData['pmt_rows_data'][] = $data;
        $this->_formData['pmt_rows']++;

    }

    private function filterFieldsLength()
    {
        $originalData = array();
        foreach ($this->_formData as $key => $data) {
            $originalData[$key] = $data;
        }
        $this->_originalFormData = $originalData;

        $changes = FALSE;
        foreach ($this->_formData as $key => $data) {
            if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_compulsoryData)) ||
                array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowCompulsoryData)
            ) {
                if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
                    throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long."));
                } else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
                    $this->_formData[$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
                    $this->_formData[$key] = mb_convert_encoding($this->_formData[$key], $this->_charset, $this->_charset);
                    $changes = true;
                }
                continue;
            } else if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_optionalData) && mb_strlen($data)) ||
                (array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowOptionalData) && mb_strlen($data))
            ) {
                if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
                    throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long."));
                } else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
                    $this->_formData[$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
                    $this->_formData[$key] = mb_convert_encoding($this->_formData[$key], $this->_charset, $this->_charset);
                    $changes = true;
                }
                continue;
            }
        }

        foreach ($this->_formData["pmt_rows_data"] as $i => $product) {
            if (array_key_exists('pmt_row_name', $product) && array_key_exists('pmt_row_desc', $product)) {
                if (!trim($product['pmt_row_name'])) {
                    $this->_formData["pmt_rows_data"][$i]['pmt_row_name'] = $product['pmt_row_name'] = $product['pmt_row_desc'];
                } else if (!trim($product['pmt_row_desc'])) {
                    $this->_formData["pmt_rows_data"][$i]['pmt_row_desc'] = $product['pmt_row_desc'] = $product['pmt_row_name'];
                }

            }

            foreach ($product as $key => $data) {
                if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_compulsoryData)) ||
                    array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowCompulsoryData)
                ) {
                    if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
                        throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long."));
                    } else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
                        // auto trim
                        $this->_formData["pmt_rows_data"][$i][$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
                        $this->_formData["pmt_rows_data"][$i][$key] = mb_convert_encoding($this->_formData["pmt_rows_data"][$i][$key], $this->_charset, $this->_charset);
                        $changes = true;
                    }
                    continue;
                } else if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_optionalData) && mb_strlen($data)) ||
                    (array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowOptionalData) && mb_strlen($data))
                ) {
                    if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
                        throw new \Svea\Maksuturva\Model\Gateway\Exception(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long."));
                    } else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
                        $this->_formData["pmt_rows_data"][$i][$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
                        $this->_formData["pmt_rows_data"][$i][$key] = mb_convert_encoding($this->_formData["pmt_rows_data"][$i][$key], $this->_charset, $this->_charset);
                        $changes = true;
                    }
                    continue;
                }
            }
        }

        return $changes;
    }

    protected function generateHash()
    {
        $hashString = '';
        foreach ($this->_hashData as $hashData) {
            switch ($hashData) {
                case 'pmt_selleriban':
                case 'pmt_invoicefromseller':
                case 'pmt_paymentmethod':
                case 'pmt_buyeridentificationcode':
                    if (isset($this->_formData[$hashData])) {
                        $hashString .= $this->_formData[$hashData] . '&';
                    }
                    break;
                default:
                    $hashString .= $this->_formData[$hashData] . '&';
            }
        }

        foreach ($this->_formData['pmt_rows_data'] as $order) {
            foreach ($order as $data) {
                $hashString .= $data . '&';
            }
        }

        $hashString .= $this->_secretKey . '&';
        $hashString = preg_replace('!\s+!', ' ', $hashString);

        return hash($this->_hashAlgoDefined, $hashString);
    }

    private function convert_encoding($string_input, $encoding)
    {
        return mb_convert_encoding($string_input, $encoding);
    }

    public function setNewMasterpassConfig($args)
    {
        if ($args['encoding']) {
            $this->_charset = $args['encoding'];
            $this->_charsethttp = $args['encoding'];
        }

        $data = $args['options'];

        $this->_secretKey = $args['secretkey'];

        $this->_formData['pmt_action'] = 'NEW_PAYMENT_EXTENDED';
        $this->_formData['pmt_version'] = '0004';
        $this->_formData['pmt_escrow'] = $data['pmt_escrow'];
        $this->_formData['pmt_keygeneration'] = '001';
        $this->_formData['pmt_currency'] = 'EUR';
        $this->_formData['pmt_escrowchangeallowed'] = 'N';

        $this->_formData['pmt_charset'] = $this->_charset;
        $this->_formData['pmt_charsethttp'] = $this->_charsethttp;

        // Force to cut off all amps and merge in current array_data
        foreach ($data as $key => $value) {
            if ($key == 'pmt_rows_data') {
                $rows = array();
                foreach ($value as $k => $v) {
                    $v = preg_replace('!\s+!', ' ', $v);
                    $rows[$k] = str_replace('&amp;', '', $v);
                }
                $this->_formData[$key] = $rows;
            } else {
                $value = preg_replace('!\s+!', ' ', $value);
                $this->_formData[$key] = str_replace('&amp;', '', $value);
            }
        }
        $hashAlgos = hash_algos();

        if (in_array("sha512", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-512';
            $this->_hashAlgoDefined = "sha512";
        } else if (in_array("sha256", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-256';
            $this->_hashAlgoDefined = "sha256";
        } else if (in_array("sha1", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-1';
            $this->_hashAlgoDefined = "sha1";
        } else if (in_array("md5", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'MD5';
            $this->_hashAlgoDefined = "md5";
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array('the hash algorithms SHA-512, SHA-256, SHA-1 and MD5 are not supported!'), \Svea\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED);
        }
        return $this;
    }
}