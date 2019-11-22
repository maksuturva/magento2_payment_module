<?php
namespace Svea\Maksuturva\Model\Source;

class AllowedLocale
{
    public function toOptionArray()
    {
        $array = array(
            array('value' => 'fi', 'label' => __('Finland')),
            array('value' => 'sv', 'label' => __('Sweden')),
            array('value' => 'en', 'label' => __('United States')),
        );

        return $array;
    }
}
