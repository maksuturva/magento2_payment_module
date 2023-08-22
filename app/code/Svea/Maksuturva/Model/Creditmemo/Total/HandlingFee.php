<?php

namespace Svea\Maksuturva\Model\Creditmemo\Total;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class HandlingFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     *
     * @return $this
     * @throws LocalizedException
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $orderHandlingFee = $order->getHandlingFee();
        $allowedAmount = $orderHandlingFee - $order->getRefundedHandlingFee();
        if ($creditmemo->hasHandlingFee()) {

            $desiredAmount = round($creditmemo->getHandlingFee() ?? 0, 2);

            if ($this->exceededMaximumAllowedAmount($allowedAmount, $desiredAmount)) {
                $allowedAmount = $order->getBaseCurrency()->format($allowedAmount, null, false);
                throw new LocalizedException(
                    \__('Maximum handling fee amount allowed to refund is: %1', $allowedAmount)
                );
            }

            $handlingFee = $desiredAmount;
        } else {
            $handlingFee = $allowedAmount;
        }

        $creditmemo->setHandlingFee($handlingFee);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $handlingFee);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $handlingFee);

        return $this;
    }

    /**
     * Note: ($x > $y + 0.0001) means ($x >= $y) for floats
     *
     * @param $allowedAmount
     * @param float $desiredAmount
     *
     * @return bool
     */
    public function exceededMaximumAllowedAmount($allowedAmount, $desiredAmount)
    {
        return (float)$desiredAmount > round((float)$allowedAmount) + 0.0001;
    }
}
