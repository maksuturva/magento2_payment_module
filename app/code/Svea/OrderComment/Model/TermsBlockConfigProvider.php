<?php

namespace Svea\OrderComment\Model;

use Magento\Catalog\Model\Config\Source\Price\Scope;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Svea\OrderComment\Model\Data\OrderComment;
use Magento\Store\Model\ScopeInterface;
use Svea\OrderComment\Model\Data\Terms\GenerateTermsUrl;

class TermsBlockConfigProvider implements ConfigProviderInterface
{
    const XML_PATH_ORDER_COMMENT_ENABLED = 'maksuturva_config/maksuturva_terms/enabled';
    const CONFIG_TEXT = 'maksuturva_config/maksuturva_terms/text';
    const CONFIG_TERMS_URL = 'maksuturva_config/maksuturva_terms/terms_url';
    const CONFIG_URL_PART = 'maksuturva_config/maksuturva_terms/url_part';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private $checkoutSession;

    /**
     * @var string
     */
    private $orderComment;

    /** @var GenerateTermsUrl */
    private $generateTermsUrl;

    /**
     * TermsBlockConfigProvider constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param GenerateTermsUrl $generateTermsUrl
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        GenerateTermsUrl $generateTermsUrl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->generateTermsUrl = $generateTermsUrl;
    }

    public function getConfig()
    {
        return [
            'maksuturva' => [
                'terms' => [
                    'enabled' => $this->isEnabled(),
                    'text' => $this->getTerms(),
                ],
            ],
        ];
    }

    private function getTerms()
    {
        $text = $this->scopeConfig->getValue(self::CONFIG_TEXT, ScopeInterface::SCOPE_STORE);
        $termsUrl = $this->scopeConfig->getValue(self::CONFIG_TERMS_URL, ScopeInterface::SCOPE_STORE);
        $urlPart = $this->scopeConfig->getValue(self::CONFIG_URL_PART, ScopeInterface::SCOPE_STORE);
        return $this->generateTermsUrlAndText($termsUrl, $urlPart, $text);
    }
    
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ORDER_COMMENT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $termsUrl
     * @param $urlPart
     * @param $text
     * @return array
     */
    private function generateTermsUrlAndText($termsUrl, $urlPart, $text): array
    {
        return $this->generateTermsUrl->generateTermsUrlAndText($termsUrl, $urlPart, $text);
    }
}
