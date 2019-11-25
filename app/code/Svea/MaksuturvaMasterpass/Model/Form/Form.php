<?php
namespace Svea\MaksuturvaMasterpass\Model\Form;
class Form extends \Svea\Maksuturva\Model\Form
{
    protected $_formData;
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
        /*'pmt_hashversion',               //alphanumeric        max lenght 10             -               {SHA-512, SHA-256, SHA-1, MD5}*/
        /*'pmt_hash',                      //alphanumeric        max lenght 128       min lenght 32*/
        'pmt_keygeneration',             //numeric             max lenght 3              -
    );

    protected $_fieldLength = array(
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
        'pmt_buyeremail' => array(0, 100),    // opt
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

    public function setConfig($args)
    {
        parent::setConfig($args);
        $this->_formData['pmt_paymentmethod'] = \Svea\MaksuturvaMasterpass\Model\Masterpass::MAKSUTURVA_MASTERPASS_METHOD_CODE;
    }
}