<?php
namespace Svea\Maksuturva\Controller\Index;

class Template extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    )
    {
        $this->_resultPageFactory = $resultLayoutFactory; 
        parent::__construct($context);
    }

    public function execute()
    {
        $this->getResponse()->setBody($this->_resultPageFactory->create()->getLayout()->createBlock('Svea\Maksuturva\Block\Form\Maksuturva')->toHtml());
    }

}