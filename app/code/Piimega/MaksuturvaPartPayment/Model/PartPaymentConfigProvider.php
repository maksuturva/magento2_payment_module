<?php
namespace Piimega\MaksuturvaPartPayment\Model;

class PartPaymentConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
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
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaPartPayment\Block\Form\PartPayment');
        $html = $block->toHtml();
        $data = ['html' => $html, 'defaultPaymentMethod'=>$block->getDefaultPaymentMethod()];
        return [
            'payment' => [
                'maksuturva_part_payment_payment' => $data
            ]
        ];
    }
}
