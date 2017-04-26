<?php
namespace Piimega\MaksuturvaInvoice\Model;

class InvoiceConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
{
    protected $_blockFactory;

    public function __construct(
        \Magento\Framework\View\Element\BlockFactory $blockFactory
    )
    {
        $this->_blockFactory = $blockFactory;
    }
    public function getConfig()
    {
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaInvoice\Block\Form\Invoice');
        $html = $block->toHtml();
        $data = ['html' => $html, 'defaultPaymentMethod'=>$block->getDefaultPaymentMethod()];
        return [
            'payment' => [
                'maksuturva_invoice_payment' => $data
            ]
        ];
    }
}
