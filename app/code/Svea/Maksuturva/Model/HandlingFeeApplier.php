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
    public function updateHandlingFee($paymentMethod, $quote)
    {
        if (isset($paymentMethod['method'])) {
            $quote->getPayment()->setMethod($paymentMethod['method']);
        }
        $quote->unsTotalsCollectedFlag();
        $quote->collectTotals();
        $quote->setTriggerRecollect(true);
        $this->quoteRepository->save($quote);
    }
}