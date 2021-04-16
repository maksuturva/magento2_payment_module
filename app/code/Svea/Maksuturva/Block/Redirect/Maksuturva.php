<?php
namespace Svea\Maksuturva\Block\Redirect;

class Maksuturva extends \Magento\Payment\Block\Form
{
    protected $_formFactory;
    protected $logger;
    
    public function __construct(
    		\Magento\Framework\View\Element\Template\Context $context,
    		\Magento\Framework\Data\FormFactory $formFactory,
    		array $data = []
    ){
        $this->_formFactory = $formFactory;
        $this->logger = $context->getLogger();
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $standard = $this->getPaymentMethodInstance();
        $standard->setOrder($this->getOrder());
        $fields = $standard->getCheckoutFormFields();
        $form = $this->_formFactory->create()
            ->setAction($standard->getPaymentRequestUrl())
            ->setId('maksuturva_payment_checkout')
            ->setName('maksuturva_payment_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($fields as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $html = '<html><head></head><body>';
        $html .= __('You will be redirected to the Svea Payments website in a few seconds.');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("maksuturva_payment_checkout").submit();</script>';
        $html .= '</body></html>';
        $this->logger->info(json_encode($fields));
        return $html;
    }
}
