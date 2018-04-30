<?php
namespace Piimega\MaksuturvaBase\Model;
class BaseConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaBase\Model\Base $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
        $this->preselectRequired = 0;
    }

    protected function getTemplate()
    {
        $template = "Piimega_Maksuturva/payment/icon";
        return $template;
    }
}
