<?php
namespace Piimega\MaksuturvaPartPayment\Model;

class PartPaymentConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaPartPayment\Model\PartPayment $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
