<?php

namespace Svea\Maksuturva\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Svea\Maksuturva\Model\ResourceModel\HandlingFeeResource;

class Config
{
    const CAN_CANCEL_SETTLED = "maksuturva_config/maksuturva_payment/can_cancel_settled";
    const CARD_HANDLING_FEE = "payment/maksuturva_card_payment/handling_fee";
    const COD_HANDLING_FEE = "payment/maksuturva_cod_payment/handling_fee";
    const GENERIC_HANDLING_FEE = "payment/maksuturva_generic_payment/handling_fee";
    const INVOICE_HANDLING_FEE = "payment\maksuturva_invoice_payment/handling_fee";
    const PART_HANDLING_FEE = "payment/maksuturva_part_payment_payment/handling_fee";

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

    /**
     * @return bool
     */
    public function canCancelSettled()
    {
        return $this->scopeConfig->getValue(self::CAN_CANCEL_SETTLED);
    }

    /**
     * @return array
     */
    public function getCardHandlingFee()
    {
        $value = [
            HandlingFeeResource::CARD_PAYMENT => $this->scopeConfig->getValue(self::CARD_HANDLING_FEE)
        ];

        return $this->formatValue($value);
    }

    /**
     * @return array
     */
    public function getCodHandlingFee()
    {
        $value = [
            HandlingFeeResource::COD_PAYMENT => $this->scopeConfig->getValue(self::COD_HANDLING_FEE)
        ];

        return $this->formatValue($value);
    }

    /**
     * @return array
     */
    public function getGenericHandlingFee()
    {
        $value = [
            HandlingFeeResource::GENERIC_PAYMENT => $this->scopeConfig->getValue(self::GENERIC_HANDLING_FEE)
        ];

        return $this->formatValue($value);
    }

    /**
     * @return array
     */
    public function getInvoiceHandlingFee()
    {
        $value = [
            HandlingFeeResource::INVOICE_PAYMENT => $this->scopeConfig->getValue(self::INVOICE_HANDLING_FEE)
        ];

        return $this->formatValue($value);
    }

    /**
     * @return array
     */
    public function getPartHandlingFee()
    {
        $value = [
            HandlingFeeResource::PART_PAYMENT => $this->scopeConfig->getValue(self::PART_HANDLING_FEE)
        ];

        return $this->formatValue($value);
    }

    /**
     * @return false|string[]
     */
    public function getHandlingFee()
    {
        $value = array_merge(
            $this->getCardHandlingFee(),
            $this->getCodHandlingFee(),
            $this->getGenericHandlingFee(),
            $this->getInvoiceHandlingFee(),
            $this->getPartHandlingFee()
        );

        return \array_filter($value);
    }

    /**
     * Formats the semi colon separated config values as an array
     * @param $value
     * @return array
     */
    private function formatValue($value)
    {
        return array_map('trim', $value);
    }
}