<?php

namespace Svea\OrderComment\Observer;

use Svea\OrderComment\Model\Data\OrderComment;

class AddOrderCommentToOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Svea\OrderComment\Model\OrderCommentConfigProvider
     */
    private $config;

    /**
     * AddOrderCommentToOrder constructor.
     *
     * @param \Svea\OrderComment\Model\OrderCommentConfigProvider $config
     */
    public function __construct(
        \Svea\OrderComment\Model\OrderCommentConfigProvider $config
    ) {
        $this->config = $config;
    }

    /**
     * Transfer the order comment from the quote object to the order object during the event
     *
     * @event sales_model_service_quote_submit_before
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->isEnabled()) {
            /* @var $order \Magento\Sales\Model\Order */
            $order = $observer->getEvent()->getOrder();

            /** @var $quote \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            $order->setData(
                OrderComment::COMMENT_FIELD_NAME,
                $quote->getData(OrderComment::COMMENT_FIELD_NAME)
            );
        }
    }
}
