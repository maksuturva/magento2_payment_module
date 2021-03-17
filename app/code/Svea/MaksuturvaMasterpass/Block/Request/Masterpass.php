<?php
namespace Svea\MaksuturvaMasterpass\Block\Request;

class Masterpass extends \Magento\Payment\Block\Form
{
    protected $_formFactory;
    protected $logger;
    
    public function __construct(
    		\Magento\Framework\View\Element\Template\Context $context,
    		\Magento\Framework\Data\FormFactory $formFactory,
            \Psr\Log\LoggerInterface $logger,
    		array $data = []
    ){
        $this->logger = $logger;
        $this->_formFactory = $formFactory;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $standard = $this->getPaymentMethodInstance();
        $standard->setQuote($this->getQuote());
        $fields = $standard->createNewMasterpassPayment();
        $form = $this->_formFactory->create()
            ->setAction($standard->getPaymentRequestUrl())
            ->setId('new_masterpass_payment')
            ->setName('new_masterpass_payment')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($fields as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $html = '<html><head></head><body>';
        $html .= __('You will be redirected to the Svea Payments website in a few seconds.');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("new_masterpass_payment").submit();</script>';
        $html .= '</body></html>';
        $this->logger->info(json_encode($fields));
        return $html;
    }
}
