<?php
namespace Piimega\Maksuturva\Block\Form;
class Maksuturva extends \Magento\Payment\Block\Form
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
    		\Piimega\Maksuturva\Model\Payment $maksuturvaModel,
    		array $data = []
	)
    {
    	$this->_objectManager = $objectManager;
    	$this->method = $maksuturvaModel;
		$this->setData('method', $this->method);
        parent::__construct($context, $data);
    }


	public function getPaymentMethods()
	{
		return $this->method->getMethods();
	}

	public function getSelectedMethod()
	{
		return $this->method->getSelectedMethod();
	}

	public function getFormType()
	{
		return $this->method->getConfigData('preselect_form_type');
	}

	public function getMethodCode()
	{
		return $this->method->getCode();
	}

	public function getDefaultPaymentMethod()
	{
		return $this->method->getConfigData('default_preselect_method');
	}
}
