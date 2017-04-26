<?php
namespace Piimega\MaksuturvaCod\Model;

class CodConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
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
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaCod\Block\Form\Cod');
        $html = $block->toHtml();
        $data = ['html' => $html, 'defaultPaymentMethod'=>$block->getDefaultPaymentMethod()];
        return [
            'payment' => [
                'maksuturva_cod_payment' => $data
            ]
        ];
    }
}
