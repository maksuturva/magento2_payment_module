<?php

namespace Svea\Maksuturva\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const CAN_CANCEL_SETTLED = "maksuturva_config\maksuturva_payment\can_cancel_settled";

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function canCancelSettled()
    {
        return $this->scopeConfig->getValue(self::CAN_CANCEL_SETTLED);
    }

}