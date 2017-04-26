<?php
namespace Piimega\MaksuturvaGeneric\Model;

class GenericConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
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
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaGeneric\Block\Form\Generic');
        $html = $block->toHtml();
        $data = ['html' => $html, 'defaultPaymentMethod'=>$block->getDefaultPaymentMethod()];
        return [
            'payment' => [
                'maksuturva_generic_payment' => $data
            ]
        ];
    }
}
