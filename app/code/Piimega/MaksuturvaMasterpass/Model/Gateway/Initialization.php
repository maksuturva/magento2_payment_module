<?php
namespace Piimega\MaksuturvaMasterpass\Model\Gateway;
class Initialization extends \Piimega\MaksuturvaMasterpass\Model\Gateway\Implementation
{
    public function getForm()
    {
        if (!$this->form) {
            $builder = $this->getQuoteFormFieldBuilder();
            $this->form = $this->_getForm($builder, $this->_maksuturvaForm);
        }

        return $this->form;
    }
}