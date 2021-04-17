<?php

namespace Svea\OrderComment\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Model\Order;
use Svea\OrderComment\Model\Data\OrderComment;
use function nl2br;
use function strlen;
use function trim;

class Comment extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    public function __construct(
        TemplateContext $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_template = 'order/view/comment.phtml';

        parent::__construct($context, $data);
    }

    public function getOrder() : Order
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderComment(): string
    {
        return trim($this->getOrder()->getData(OrderComment::COMMENT_FIELD_NAME));
    }

    public function hasOrderComment() : bool
    {
        return strlen($this->getOrderComment()) > 0;
    }

    public function getOrderCommentHtml() : string
    {
        return nl2br($this->escapeHtml($this->getOrderComment()));
    }
}
