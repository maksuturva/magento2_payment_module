<?php
namespace Svea\Maksuturva\Api;

use Magento\Quote\Model\Quote;

interface HandlingFeeApplierInterface
{
    /**
     * @param mixed $paymentMethod
     * @param Quote $quote
     * @return mixed
     */
    public function updateHandlingFee($paymentMethod, $quote);
}