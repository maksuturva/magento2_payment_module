<?php
namespace Svea\MaksuturvaMasterpass\Model\Gateway;
class Payment extends \Svea\MaksuturvaMasterpass\Model\Gateway\Implementation
{
    public function getForm()
    {
        if (! $this->form) {
            $fieldBuilder = $this->getOrderFormFieldBuilder();
            $this->form = $this->_getForm($fieldBuilder, $this->_maksuturvaForm);
        }
        return $this->form;
    }
}