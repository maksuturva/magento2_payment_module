<?php
namespace Svea\MaksuturvaCollated\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CollatedConfigProvider
 *
 * @package Svea\MaksuturvaCollated\Model
 */
class CollatedConfigProvider extends \Svea\Maksuturva\Model\ConfigProvider
{
    const CONFIG_SUBPAYMENT_PATH = 'payment/maksuturva_collated_payment/maksuturva_collated_subpayments/';
    const SUBPAYMENT_STEPS =[
        'pay_later',
        'pay_now_other',
        'pay_now_bank',
    ];
    const PAY_LATER = 'pay_later';
    const PAY_NOW_OTHER = 'pay_now_other';
    const PAY_NOW_BANK = 'pay_now_bank';

    /**
     * CollatedConfigProvider constructor.
     *
     * @param Collated $maksuturvaModel
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Svea\MaksuturvaCollated\Model\Collated $maksuturvaModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($maksuturvaModel, $scopeConfig);
    }

    public function getTemplate()
    {
        return "Svea_MaksuturvaCollated/payment/icons_form_collated";
    }

    public function getConfig()
    {

        //set payment initial data
        $data['methods'] = $this->getPaymentMethods();
        $data['defaultPaymentMethod'] = $this->getDefaultPaymentMethod();
        $data['template'] = $this->getTemplate();
        $data['preselectRequired'] = $this->preselectRequired;
        $data['titles'] = $this->getSubpaymentTitles();

        $paymentArray = [
            'payment' => []
        ];
        foreach (self::SUBPAYMENT_STEPS as $subpaymentStep) {
            $methods = $data['methods'][$subpaymentStep] ?? null;
            $paymentArray['payment'][$subpaymentStep] = [
                'title' => $data['titles'][$subpaymentStep],
                'methods' => $methods,
                'defaultPaymentMethod' => '',
                'preselectRequired' => $this->preselectRequired,
            ];
        }
        $paymentArray['payment'][$this->getMethodCode()] = $data;
        return $paymentArray;
    }

    private function getSubpaymentTitles(): array
    {
        $titles = [];
        foreach (self::SUBPAYMENT_STEPS as $SUBPAYMENT_STEP) {
            $titles[$SUBPAYMENT_STEP] = $this->scopeConfig->getValue(self::CONFIG_SUBPAYMENT_PATH . $SUBPAYMENT_STEP, 'store' );
        }
        return $titles;
    }
}
