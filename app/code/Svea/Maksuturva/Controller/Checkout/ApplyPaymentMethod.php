<?php

namespace Svea\Maksuturva\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\LayoutFactory;
use Svea\Maksuturva\Model\HandlingFeeApplier;

class ApplyPaymentMethod extends Action
{
    /**
     * @var ForwardFactory
     */
    private $resultForwardFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var HandlingFeeApplier
     */
    private $handlingApplier;

    /**
     * ApplyPaymentMethod constructor.
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param LayoutFactory $layoutFactory
     * @param Session $checkoutSession
     * @param HandlingFeeApplier $handlingApplier
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        LayoutFactory $layoutFactory,
        Session $checkoutSession,
        HandlingFeeApplier $handlingApplier
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->layoutFactory = $layoutFactory;
        $this->checkoutSession = $checkoutSession;
        $this->handlingApplier = $handlingApplier;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $paymentMethod = $this->getRequest()->getParam('payment_method');
        $collatedPaymentMethod = $this->getRequest()->getParam('collated_method') ?? null;
        $quote = $this->checkoutSession->getQuote();
        if ($collatedPaymentMethod) {
            $this->handlingApplier->updateHandlingFee($paymentMethod, $quote, $collatedPaymentMethod);
        } else {
            $this->handlingApplier->updateHandlingFee($paymentMethod, $quote);
        }
    }
}