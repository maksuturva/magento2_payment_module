<?php
namespace Svea\Maksuturva\Block\Form;
class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        $fieldConfig = $element->getFieldConfig();
        $html = "";
        if(isset($fieldConfig['id']) && $fieldConfig['id']=="manually_query_in_hour"){
            $url = $this->getUrl('maksuturva/index/run');
            $html = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(
                    [
                        'label' => __('Manually Query Maksuturva API For Orders Created In Past 2 Hours'),
                        'onclick' => "setLocation('$url')",
                        'class' => 'action-add',
                    ]
                )->toHtml();
        }elseif(isset($fieldConfig['id']) && $fieldConfig['id']=="manually_query_in_day"){
            $url = $this->getUrl('maksuturva/index/run/', ['is_long_term' => true]);
            $html = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(
                    [
                        'label' => __('Manually Query Maksuturva API For Orders Created In Past Week'),
                        'onclick' => "setLocation('$url')",
                        'class' => 'action-add',
                    ]
                )->toHtml();
        }
        return $html;
    }
}