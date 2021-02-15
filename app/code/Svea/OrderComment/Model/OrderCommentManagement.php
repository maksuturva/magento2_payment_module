<?php

namespace Svea\OrderComment\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Svea\OrderComment\Model\Data\OrderComment;
use function __;
use function mb_strlen;
use function strip_tags;

class OrderCommentManagement implements \Svea\OrderComment\Api\OrderCommentManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Svea\OrderComment\Model\ResourceModel\QuoteAttribute
     */
    private $quoteAttributeResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Svea\OrderComment\Model\ResourceModel\QuoteAttribute $quoteAttributeResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteAttributeResource = $quoteAttributeResource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int                                                $cartId
     * @param \Svea\OrderComment\Api\Data\OrderCommentInterface $orderComment
     *
     * @return null|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function saveOrderComment(
        $cartId,
        \Svea\OrderComment\Api\Data\OrderCommentInterface $orderComment
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $comment = strip_tags($orderComment->getComment() ?: '');

        $this->validateComment($comment);

        if ($quote->getData(OrderComment::COMMENT_FIELD_NAME) !== $comment) {
            try {
                $quote->setData(OrderComment::COMMENT_FIELD_NAME, $comment);
                $this->quoteAttributeResource->saveAttribute($quote, OrderComment::COMMENT_FIELD_NAME);
            } catch (\Exception $ex) {
                throw new CouldNotSaveException(__('The order comment could not be saved'), $ex);
            }
        }

        return $comment;
    }

    /**
     * @param string $comment
     * @throws ValidatorException
     */
    private function validateComment($comment)
    {
        $maxLength = $this->scopeConfig->getValue(OrderCommentConfigProvider::CONFIG_MAX_LENGTH);

        if ($maxLength && (mb_strlen($comment) > $maxLength)) {
            throw new ValidatorException(__('Comment is too long'));
        }
    }
}
