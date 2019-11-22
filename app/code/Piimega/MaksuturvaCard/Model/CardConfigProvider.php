<?php
namespace Svea\MaksuturvaCard\Model;

class CardConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Svea\MaksuturvaCard\Model\Card $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
