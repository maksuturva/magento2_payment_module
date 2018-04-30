<?php
namespace Piimega\MaksuturvaCod\Model;

class CodConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaCod\Model\Cod $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
