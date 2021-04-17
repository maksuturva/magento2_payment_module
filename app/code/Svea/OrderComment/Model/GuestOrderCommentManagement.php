<?php

namespace Svea\OrderComment\Model;

class GuestOrderCommentManagement implements \Svea\OrderComment\Api\GuestOrderCommentManagementInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \Svea\OrderComment\Api\OrderCommentManagementInterface
     */
    private $management;

    public function __construct(
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Svea\OrderComment\Api\OrderCommentManagementInterface $management
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->management = $management;
    }

    /**
     * @inheritDoc
     */
    public function saveOrderComment(
        $cartId,
        \Svea\OrderComment\Api\Data\OrderCommentInterface $orderComment
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->management->saveOrderComment($quoteIdMask->getQuoteId(), $orderComment);
    }
}
