<?php
namespace Svea\Maksuturva\Model\System\Config\Source;
class Formtype
{
    public function toOptionArray()
    {
        $array = array(
            array('value' => \Svea\Maksuturva\Block\Form\Maksuturva::FORMTYPE_DROPDOWN, 'label' => 'Dropdown'),
            array('value' => \Svea\Maksuturva\Block\Form\Maksuturva::FORMTYPE_ICONS, 'label' => 'Icons'),
            array('value' => \Svea\Maksuturva\Block\Form\Maksuturva::COLLATED_ICONS, 'label' => 'Icons for Collated payment'),
        );

        return $array;
    }
}
