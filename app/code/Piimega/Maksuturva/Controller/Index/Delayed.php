<?php
namespace Piimega\Maksuturva\Controller\Index;

class Delayed extends \Piimega\Maksuturva\Controller\Maksuturva
{
    protected $_quoteFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
        array $data = []
    )
    {
        parent::__construct($context, $orderFactory, $logger, $scopeConfig, $quoteRepository, $checkoutsession, $maksuturvaHelper, $data);
        $this->_quoteFactory = $quoteFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $order = $this->getLastedOrder();

        if(!$this->validateReturnedOrder($order, $params)){
            $this->_redirect('maksuturva/index/error', array('type' => \Piimega\Maksuturva\Model\Payment::ERROR_VALUES_MISMATCH, 'message' => __('Unknown error on maksuturva payment module.')));
            return;
        }

        if ($order->getId()) {
            $order = $this->getLastedOrder();

            if ($order->getId()) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true, __('Waiting for delayed payment confirmation from Maksuturva'))->save();
            }
            $this->disableQuote($order);
        }

        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
}