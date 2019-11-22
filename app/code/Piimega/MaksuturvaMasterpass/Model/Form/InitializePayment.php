<?php
namespace Svea\MaksuturvaMasterpass\Model\Form;
class InitializePayment extends \Svea\MaksuturvaMasterpass\Model\Form\Form
{
    public function setConfig($params)
    {
        parent::setConfig($params);
        $this->_formData['pmt_version'] = '5104';
        $this->_formData["pmt_buyeremail"] = "masterpass@maksuturva.fi";
        $this->_formData["pmt_buyername"] = "Masterpass";
        $this->_formData["pmt_buyeraddress"] = "none";
        $this->_formData["pmt_buyerpostalcode"]= "00000";
        $this->_formData["pmt_buyercity"] = "none";
        $this->_formData["pmt_buyercountry"] = "FI";
        $this->_formData["pmt_deliveryname"] = "Masterpass";
        $this->_formData["pmt_deliveryaddress"] = "none";
        $this->_formData["pmt_deliverypostalcode"] = "00000";
        $this->_formData["pmt_deliverycity"] = "none";
        $this->_formData["pmt_deliverycountry"] = "FI";

        return $this;
    }
}