<?php
namespace Piimega\MaksuturvaCard\Model;

class CardConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaCard\Model\Card $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
