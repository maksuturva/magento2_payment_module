<?php
namespace Svea\Maksuturva\Block\Form;
class Maksuturva extends \Magento\Payment\Block\Form
{
    protected $_template; 
    protected $_maksuturvaModel;

	const FORMTYPE_DROPDOWN = 0;
	const FORMTYPE_ICONS = 1;
    
    public function __construct( 
    		\Magento\Framework\View\Element\Template\Context $context,
    		\Svea\Maksuturva\Model\PaymentAbstract $maksuturvaModel,
    		array $data = []
	)
    {
    	$this->method = $maksuturvaModel;
		$this->setData('method', $this->method);
        parent::__construct($context, $data);
    }


	public function getPaymentMethods()
	{
		return $this->method->getMethods();
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
