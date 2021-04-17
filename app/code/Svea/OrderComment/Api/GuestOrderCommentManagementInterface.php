<?php

namespace Svea\OrderComment\Api;

/**
 * Interface for saving the checkout comment to the quote for guest orders
 */
interface GuestOrderCommentManagementInterface
{
    /**
     * @param string $cartId
     * @param \Svea\OrderComment\Api\Data\OrderCommentInterface $orderComment
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function saveOrderComment(
        $cartId,
        \Svea\OrderComment\Api\Data\OrderCommentInterface $orderComment
    );
}
