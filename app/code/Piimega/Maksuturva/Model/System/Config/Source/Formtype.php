<?php
namespace Piimega\Maksuturva\Model\System\Config\Source;
class Formtype
{
    public function toOptionArray()
    {
        $array = array(
            array('value' => \Piimega\Maksuturva\Block\Form\Maksuturva::FORMTYPE_DROPDOWN, 'label' => 'Dropdown'),
            array('value' => \Piimega\Maksuturva\Block\Form\Maksuturva::FORMTYPE_ICONS, 'label' => 'Icons'),
        );

        return $array;
    }
}
