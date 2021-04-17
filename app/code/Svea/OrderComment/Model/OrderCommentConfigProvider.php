<?php

namespace Svea\OrderComment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Svea\OrderComment\Model\Data\OrderComment;

class OrderCommentConfigProvider implements ConfigProviderInterface
{
    const CONFIG_MAX_LENGTH = 'sales/ordercomments/max_length';
    const CONFIG_COMMENT_HELP = 'sales/ordercomments/comment_help';
    const CONFIG_FIELD_COLLAPSE_STATE = 'sales/ordercomments/collapse_state';
    const XML_PATH_ORDER_COMMENT_ENABLED = 'sales/ordercomments/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private $checkoutSession;

    /**
     * @var string
     */
    private $orderComment;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
    }

    public function getConfig()
    {
        return [
            'sales' => [
                'ordercomments' => [
                    'enabled' => $this->isEnabled(),
                    'comment_help' => $this->scopeConfig->getValue(self::CONFIG_COMMENT_HELP),
                    'value'   => $this->getOrderComment(),
                    'max_length' => (int)$this->scopeConfig->getValue(self::CONFIG_MAX_LENGTH),
                    'comment_initial_collapse_state'
                        => (int)$this->scopeConfig->getValue(self::CONFIG_FIELD_COLLAPSE_STATE),
                ],
            ],
        ];
    }

    private function getOrderComment()
    {
        if ($this->orderComment === null) {
            $quote = $this->checkoutSession->getQuote();
            $this->orderComment = $quote ? (string)$quote->getData(OrderComment::COMMENT_FIELD_NAME) : '';
        }

        return $this->orderComment;
    }

    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ORDER_COMMENT_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
