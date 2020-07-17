<?php

namespace Svea\Maksuturva\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class HandlingFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $orderHandlingFee = $order->getHandlingFee();
        $allowedAmount = $orderHandlingFee - $order->getRefundedHandlingFee();
        $desiredAmount = round($creditmemo->getBaseHandlingFee(), 2);

        // Note: ($x > $y + 0.0001) means ($x >= $y) for floats
        if ($desiredAmount > round($allowedAmount) + 0.0001) {
            $allowedAmount = $order->getBaseCurrency()->format($allowedAmount, null, false);
            throw new \Magento\Framework\Exception\LocalizedException(
                \__('Maximum handling fee amount allowed to refund is: %1', $allowedAmount)
            );
        }

        $creditmemo->setHandlingFee($allowedAmount);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $desiredAmount);

        return $this;
    }
}