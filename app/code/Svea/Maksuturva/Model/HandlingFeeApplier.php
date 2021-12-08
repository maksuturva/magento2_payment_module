<?php
namespace Svea\Maksuturva\Model;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Svea\Maksuturva\Api\HandlingFeeApplierInterface;

class HandlingFeeApplier implements HandlingFeeApplierInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * HandlingFeeApplier constructor.
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritDoc
     */
    public function updateHandlingFee($quote, $paymentMethod = ' ', $subMethod = null, $collatedMethod = null)
    {
        if (\is_string($paymentMethod)) {
            $quote->getPayment()->setMethod($paymentMethod);
        } elseif (isset($paymentMethod['method'])) {
            $quote->getPayment()->setMethod($paymentMethod['method']);
        }
        $quote->getPayment()->setAdditionalInformation('sub_payment_method', $subMethod);
        $quote->getPayment()->setAdditionalInformation('collated_method', $collatedMethod);
        $quote->unsTotalsCollectedFlag();
        $quote->collectTotals();
        $quote->setTriggerRecollect(true);
        $this->quoteRepository->save($quote);
    }
}
