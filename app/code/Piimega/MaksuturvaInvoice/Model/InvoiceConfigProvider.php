<?php
namespace Piimega\MaksuturvaInvoice\Model;

class InvoiceConfigProvider extends \Piimega\Maksuturva\Model\ConfigProvider
{
    public function __construct(
        \Piimega\MaksuturvaInvoice\Model\Invoice $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }
}
