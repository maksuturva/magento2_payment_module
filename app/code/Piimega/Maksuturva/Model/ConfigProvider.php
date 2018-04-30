<?php
namespace Piimega\Maksuturva\Model;

class ConfigProvider implements \Piimega\Maksuturva\Model\ConfigProviderInterface
{
    protected $method;
    protected $scopeConfig;
    protected $preselectRequired = 1;

    public function __construct(
        \Piimega\Maksuturva\Model\PaymentAbstract $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->method = $maksuturvaModel;
        $this->scopeConfig = $scopeConfig;
    }

    protected function getPaymentMethods()
    {
        return $this->method->getMethods();
    }

    protected function getMethodCode()
    {
        return $this->method->getCode();
    }

    protected function getDefaultPaymentMethod()
    {
        return $this->method->getConfigData('default_preselect_method');
    }

    protected function getFormType()
    {
        return $this->method->getConfigData('preselect_form_type');
    }

    protected function getPreselectPaymentMethod(){
        return $this->scopeConfig->getValue(\Piimega\Maksuturva\Helper\Data::CONFIG_PRESELECT_PAYMENT_METHOD);
    }

    protected function getTemplate()
    {
        switch ($this->getFormType()) {
            case \Piimega\Maksuturva\Block\Form\Maksuturva::FORMTYPE_DROPDOWN:
                $template = "Piimega_Maksuturva/payment/select_form";
                break;
            case \Piimega\Maksuturva\Block\Form\Maksuturva::FORMTYPE_ICONS:
                $template = "Piimega_Maksuturva/payment/icons_form";
                break;
            default:
                throw new \Exception('unknown form type');
        }
        return $template;
    }

    public function getConfig()
    {
        //set payment initial data
        $data['methods'] = $this->getPaymentMethods();
        $data['defaultPaymentMethod'] = $this->getDefaultPaymentMethod();
        $data['template'] = $this->getTemplate();
        $data['preselectRequired'] = $this->preselectRequired;

        return [
            'payment' => [
                $this->getMethodCode() => $data
            ]
        ];
    }
}
