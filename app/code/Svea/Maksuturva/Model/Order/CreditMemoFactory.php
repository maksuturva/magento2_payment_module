<?php
namespace Svea\Maksuturva\Model\Order;

use Magento\Sales\Model\Order\Creditmemo;

class CreditMemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * Initialize creditmemo state based on requested parameters
     *
     * @param Creditmemo $creditmemo
     * @param array $data
     * @return void
     */
    protected function initData($creditmemo, $data)
    {
        if (isset($data['shipping_amount'])) {
            $creditmemo->setBaseShippingAmount((double)$data['shipping_amount']);
            $creditmemo->setBaseShippingInclTax((double)$data['shipping_amount']);
        }
        if (isset($data['adjustment_positive'])) {
            $creditmemo->setAdjustmentPositive($data['adjustment_positive']);
        }
        if (isset($data['adjustment_negative'])) {
            $creditmemo->setAdjustmentNegative($data['adjustment_negative']);
        }
        if (isset($data['handling_fee'])) {
            $creditmemo->setBaseHandlingFee($data['handling_fee']);
        }
    }
}