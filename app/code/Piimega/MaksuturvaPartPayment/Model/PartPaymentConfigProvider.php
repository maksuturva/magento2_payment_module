<?php
namespace Svea\MaksuturvaPartPayment\Model;

class PartPaymentConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Svea\MaksuturvaPartPayment\Model\PartPayment $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
