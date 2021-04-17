<?php

namespace Svea\OrderComment\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Svea\OrderComment\Api\Data\OrderCommentInterface;

class OrderComment extends AbstractSimpleObject implements OrderCommentInterface
{
    const COMMENT_FIELD_NAME = 'svea_order_comment';

    /**
     * @return string|null
     */
    public function getComment()
    {
        return $this->_get(static::COMMENT_FIELD_NAME);
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment($comment)
    {
        return $this->setData(static::COMMENT_FIELD_NAME, $comment);
    }
}
