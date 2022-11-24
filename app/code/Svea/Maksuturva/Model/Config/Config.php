<?php

namespace Svea\Maksuturva\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Svea\Maksuturva\Model\ResourceModel\HandlingFeeResource;

class Config
{
    const CAN_CANCEL_SETTLED = "maksuturva_config/maksuturva_payment/can_cancel_settled";
    const CARD_HANDLING_FEE = "payment/maksuturva_card_payment/handling_fee";
    const COD_HANDLING_FEE = "payment/maksuturva_cod_payment/handling_fee";
    const GENERIC_HANDLING_FEE = "payment/maksuturva_generic_payment/handling_fee";
    const INVOICE_HANDLING_FEE = "payment/maksuturva_invoice_payment/handling_fee";
    const PART_HANDLING_FEE = "payment/maksuturva_part_payment_payment/handling_fee";
    const COLLATED_LATER_HANDLING_FEE = "payment/maksuturva_collated_payment/pay_later_handling_fee";
    const COLLATED_NOW_HANDLING_FEE = "payment/maksuturva_collated_payment/pay_now_other_handling_fee";
    const COLLATED_BANK_HANDLING_FEE = "payment/maksuturva_collated_payment/pay_now_bank_handling_fee";

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
     * @param string $config
     * @param int|null $storeId
     *
     * @return mixed
     */
    private function getStoreConfig(string $config, $storeId)
    {
        return $this->scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return bool
     */
    public function canCancelSettled()
    {
        return $this->scopeConfig->getValue(self::CAN_CANCEL_SETTLED);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCardHandlingFee($storeId)
    {
        $value = [
            HandlingFeeResource::CARD_PAYMENT => $this->getStoreConfig(self::CARD_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCodHandlingFee($storeId)
    {
        $value = [
            HandlingFeeResource::COD_PAYMENT => $this->getStoreConfig(self::COD_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getGenericHandlingFee($storeId)
    {
        $value = [
            HandlingFeeResource::GENERIC_PAYMENT => $this->getStoreConfig(self::GENERIC_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getInvoiceHandlingFee($storeId)
    {
        $value = [
            HandlingFeeResource::INVOICE_PAYMENT => $this->getStoreConfig(self::INVOICE_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getPartHandlingFee($storeId)
    {
        $value = [
            HandlingFeeResource::PART_PAYMENT => $this->getStoreConfig(self::PART_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCollatedPayLaterFees($storeId)
    {
        $value = [
            HandlingFeeResource::COLLATED_LATER_PAYMENT => $this->getStoreConfig(self::COLLATED_LATER_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCollatedPayNowOtherFees($storeId)
    {
        $value = [
            HandlingFeeResource::COLLATED_NOW_PAYMENT => $this->getStoreConfig(self::COLLATED_NOW_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCollatedPayNowBankFees($storeId)
    {
        $value = [
            HandlingFeeResource::COLLATED_BANK_PAYMENT => $this->getStoreConfig(self::COLLATED_BANK_HANDLING_FEE, $storeId),
        ];

        return $this->formatValue($value);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCollatedFees($storeId)
    {
        $fees = \array_merge(
            $this->getCollatedPayLaterFees($storeId),
            $this->getCollatedPayNowOtherFees($storeId),
            $this->getCollatedPayNowBankFees($storeId)
        );

        return [
            HandlingFeeResource::COLLATED_PAYMENT => $fees
        ];
    }

    /**
     * @param int|null $storeId
     *
     * @return false|string[]
     */
    public function getHandlingFee($storeId)
    {
        $value = array_merge(
            $this->getCardHandlingFee($storeId),
            $this->getCodHandlingFee($storeId),
            $this->getGenericHandlingFee($storeId),
            $this->getInvoiceHandlingFee($storeId),
            $this->getPartHandlingFee($storeId),
            $this->getCollatedFees($storeId)
        );

        return \array_filter($value);
    }

    /**
     * Formats the semicolon-separated config values as an array
     *
     * 10;FI01=5;FI06=7.5 =>
     *  0 = 10       (default)
     *  FI01 = 5
     *  FI06 = 7.5
     *
     * @param array $value
     * @return array
     */
    private function formatValue($value)
    {
        $formatted = [];
        foreach ($value as $key => $data) {
            if ($data === null) {
                continue;
            }
            $entries = [];
            foreach (\explode(';', $data) as $entry) {
                $feeInfo = \explode('=', $entry);
                if (\count($feeInfo) == 1) {
                    $code = 0;
                    $amount = $feeInfo[0];
                } else {
                    $code = \trim($feeInfo[0]);
                    $amount = $feeInfo[1];
                }
                if (!empty($amount)) {
                    $entries[$code] = $amount;
                }
            }
            $formatted[$key] = $entries;
        }
        return $formatted;
    }
}
