<?php
namespace Piimega\Maksuturva\Model\Source;

class Encoding
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'UTF-8', 'label' => __(' UTF-8')),
            array('value' => 'ISO-8859-1', 'label' => __(' ISO-8859-1')),
        );
    }
}