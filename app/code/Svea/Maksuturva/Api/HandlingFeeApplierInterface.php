<?php
namespace Svea\Maksuturva\Api;

use Magento\Quote\Model\Quote;

interface HandlingFeeApplierInterface
{
    /**
     * @param mixed $paymentMethod
     * @param Quote $quote
     * @param string|null $subMethod
     * @param string|null $collatedMethod
     *
     * @return mixed
     */
    public function updateHandlingFee($quote, $paymentMethod = '', $subMethod = null, $collatedMethod = null);
}
