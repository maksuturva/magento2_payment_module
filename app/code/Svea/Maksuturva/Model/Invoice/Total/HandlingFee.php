<?php

namespace Svea\Maksuturva\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class HandlingFee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoice->setHandlingFee(0);

        $amount = $invoice->getOrder()->getHandlingFee();
        $invoice->setHandlingFee($amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getHandlingFee());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getHandlingFee());

        return $this;
    }
}