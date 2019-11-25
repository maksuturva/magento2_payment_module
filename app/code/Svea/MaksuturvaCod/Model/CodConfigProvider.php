<?php
namespace Svea\MaksuturvaCod\Model;

class CodConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Svea\MaksuturvaCod\Model\Cod $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
