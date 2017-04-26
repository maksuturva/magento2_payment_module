<?php
namespace Piimega\MaksuturvaBase\Model;

class BaseConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
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
        $block = $this->_blockFactory->createBlock('Piimega\MaksuturvaBase\Block\Form\Base');
        $html = $block->toHtml();
        $data = ['html' => $html];
        return [
            'payment' => [
                'maksuturva_base_payment' => $data
            ]
        ];
    }
}
