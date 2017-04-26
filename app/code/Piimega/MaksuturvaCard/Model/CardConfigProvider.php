<?php
namespace Piimega\MaksuturvaCard\Model;

class CardConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
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
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaCard\Block\Form\Card');
        $html = $block->toHtml();
        $data = ['html' => $html, 'defaultPaymentMethod'=>$block->getDefaultPaymentMethod()];
        return [
            'payment' => [
                'maksuturva_card_payment' => $data
            ]
        ];
    }
}
