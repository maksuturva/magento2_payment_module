<?php
namespace Piimega\MaksuturvaMasterpass\Model\Gateway;
class Payment extends \Piimega\MaksuturvaMasterpass\Model\Gateway\Implementation
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