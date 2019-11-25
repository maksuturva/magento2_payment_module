<?php
namespace Svea\MaksuturvaMasterpass\Model\Form;
class FinalizePayment extends \Svea\MaksuturvaMasterpass\Model\Form\Form
{
    public function setConfig($params)
    {
        parent::setConfig($params);
        $this->_formData['pmt_version'] = '5204';
        return $this;
    }
}