<?php
namespace Piimega\MaksuturvaGeneric\Model;

class GenericConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaGeneric\Model\Generic $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
