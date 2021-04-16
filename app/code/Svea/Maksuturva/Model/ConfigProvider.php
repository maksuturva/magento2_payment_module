<?php
namespace Svea\Maksuturva\Model;

class ConfigProvider implements \Svea\Maksuturva\Model\ConfigProviderInterface
{
    protected $method;
    protected $scopeConfig;
    protected $preselectRequired = 1;

    public function __construct(
        \Svea\Maksuturva\Model\PaymentAbstract $maksuturvaModel,
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
        return $this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_PRESELECT_PAYMENT_METHOD);
    }

    protected function getSellerId()
    {
        if ($this->isSandboxMode())
            return (String)$this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_TEST_SELLERID);
        else
            return (String)$this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_SELLERID);
    }

    protected function getMaksuturvaHost()
    {
        if ($this->isSandboxMode())
            return (String)$this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_MAKSUTURVA_TEST_HOST);
        else
            return (String)$this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_MAKSUTURVA_HOST);
    }

    private function isSandboxMode()
    {
        return (bool)$this->scopeConfig->getValue(\Svea\Maksuturva\Helper\Data::CONFIG_SANDBOXMODE);
    }

    protected function getTemplate()
    {
        switch ($this->getFormType()) {
            case \Svea\Maksuturva\Block\Form\Maksuturva::FORMTYPE_DROPDOWN:
                $template = "Svea_Maksuturva/payment/select_form";
                break;
            case \Svea\Maksuturva\Block\Form\Maksuturva::FORMTYPE_ICONS:
                $template = "Svea_Maksuturva/payment/icons_form";
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
        $data['api_sellerid'] = $this->getSellerId();
        $data['api_host'] = $this->getMaksuturvaHost();
        
        return [
            'payment' => [
                $this->getMethodCode() => $data
            ]
        ];
    }
}
