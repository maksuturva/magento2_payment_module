<?php
namespace Svea\MaksuturvaBase\Model;
class BaseConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Svea\MaksuturvaBase\Model\Base $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
        $this->preselectRequired = 0;
    }

    protected function getTemplate()
    {
        $template = "Svea_Maksuturva/payment/icon";
        return $template;
    }
}
