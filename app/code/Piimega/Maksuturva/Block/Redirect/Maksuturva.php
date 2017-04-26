<?php
namespace Piimega\Maksuturva\Block\Redirect;

class Maksuturva extends \Magento\Payment\Block\Form
{
    protected $_salesOrder;
    protected $_maksuturvaModel;
    protected $_checkoutSession;
    protected $_formFactory;
    protected $_objectManager;
    
    public function __construct(
    		\Magento\Framework\View\Element\Template\Context $context,
            \Magento\Sales\Model\OrderFactory $order,
            \Magento\Checkout\Model\Session $session,
    		\Magento\Framework\Data\FormFactory $formFactory,
    		\Magento\Framework\ObjectManagerInterface $objectManager,
    		array $data = []
    ){
        $this->_salesOrder = $order;
        $this->_checkoutSession = $session;
        $this->_formFactory = $formFactory;
        $this->_objectManager = $objectManager; 
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
        $html .= __('You will be redirected to the Maksuturva website in a few seconds.');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("maksuturva_payment_checkout").submit();</script>';
        $html .= '</body></html>';
        $this->_objectManager->get('Psr\Log\LoggerInterface')->info(json_encode($fields));
        return $html;
    }
}
