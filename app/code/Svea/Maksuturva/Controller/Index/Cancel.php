<?php

namespace Svea\Maksuturva\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Svea\Maksuturva\Controller\Maksuturva;
use Svea\Maksuturva\Helper\Data;
use Svea\Maksuturva\Model\HandlingFeeApplier;

class Cancel extends Maksuturva
{
    /**
     * @var HandlingFeeApplier
     */
    private $handlingFeeApplier;

    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutsession
     * @param Data $maksuturvaHelper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param array $data
     * @param HandlingFeeApplier $handlingFeeApplier
     */
    public function __construct(
        Context                         $context,
        OrderFactory                    $orderFactory,
        ScopeConfigInterface            $scopeConfig,
        CartRepositoryInterface         $quoteRepository,
        Session                         $checkoutsession,
        Data                            $maksuturvaHelper,
        OrderRepository                 $orderRepository,
        SearchCriteriaBuilder           $searchCriteriaBuilder,
        SortOrderBuilder                $sortOrderBuilder,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        HandlingFeeApplier              $handlingFeeApplier,
        array                           $data = []
    ) {
        parent::__construct(
            $context,
            $orderFactory,
            $scopeConfig,
            $quoteRepository,
            $checkoutsession,
            $maksuturvaHelper,
            $orderRepository,
            $searchCriteriaBuilder,
            $sortOrderBuilder,
            $orderPaymentRepository,
            $data
        );
        $this->handlingFeeApplier = $handlingFeeApplier;
    }

    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $params = $this->getRequest()->getParams();

        if (empty($pmt_id)) {
            $this->messageManager->addError(__('Unknown error on Svea payment module.'));
            $this->_redirect('maksuturva/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH));
            return;
        }

        $this->getHelper()->sveaLoggerDebug("Cancel action controller request for payment " . $pmt_id);

        $order = $this->getLastedOrder();
        $payment = $this->getPayment();
        $additional_data = json_decode($payment->getAdditionalData(), true);

        $quote = $this->_checkoutSession->getQuote();
        $this->handlingFeeApplier->updateHandlingFee($quote);

        if ($additional_data[\Svea\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->_redirect('checkout/cart');
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT || $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            $order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
            $order->cancel();
            $order->addStatusHistoryComment(__('Payment canceled in Svea Payments'), 'pay_aborted');

            if ($this->getConfigData('canceled_order_status')) {
                $canceledStatus = $this->getConfigData('canceled_order_status');
            } else {
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
            }
            $order->setStatus($canceledStatus, true, __('You have cancelled your payment in Svea Payments.'));
            $order->save();
            $this->getHelper()->sveaLoggerInfo("Cancel action controller, order " . $order->getIncrementId() . " cancelled for payment " . $pmt_id);

            $this->messageManager->addError(__('You have cancelled your payment in Svea Payments.'));
        } else {
            $this->messageManager->addError(__('Unable to cancel order that has already been paid.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $this->_redirect('checkout/cart');
    }

}