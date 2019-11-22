<?php
namespace Svea\MaksuturvaGeneric\Model;

class GenericConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Svea\MaksuturvaGeneric\Model\Generic $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
