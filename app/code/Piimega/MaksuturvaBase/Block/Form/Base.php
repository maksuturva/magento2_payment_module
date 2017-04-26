<?php
namespace Piimega\MaksuturvaBase\Block\Form;
class Base extends \Piimega\Maksuturva\Block\Form\Maksuturva
{
    protected $_objectManager;
    protected $_paymentConfig;
    protected $_template; 
    protected $_maksuturvaModel;

	const FORMTYPE_DROPDOWN = 0;
	const FORMTYPE_ICONS = 1;
    
    public function __construct( 
    		\Magento\Framework\View\Element\Template\Context $context,
    		\Magento\Payment\Model\Config $paymentConfig,
    		\Magento\Framework\ObjectManager\ObjectManager $objectManager,
    		\Piimega\MaksuturvaBase\Model\Base $maksuturvaModel,
    		array $data = []
	)
    {
    	$this->_objectManager = $objectManager;
    	$this->method = $maksuturvaModel;
		$this->setData('method', $this->method);
		$this->setTemplate('Piimega_MaksuturvaBase::icon.phtml');

        parent::__construct($context, $paymentConfig, $objectManager, $maksuturvaModel, $data);
    }
}
