<?php

namespace Svea\Maksuturva\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var HandlingFeeApplier
     */
    private $handlingApplier;

    /**
     * ApplyPaymentMethod constructor.
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param LayoutFactory $layoutFactory
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param HandlingFeeApplier $handlingApplier
     */
    public function __construct(
        Context              $context,
        ForwardFactory        $resultForwardFactory,
        LayoutFactory         $layoutFactory,
        Session               $checkoutSession,
        StoreManagerInterface $storeManager,
        HandlingFeeApplier    $handlingApplier
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->layoutFactory = $layoutFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->handlingApplier = $handlingApplier;

        parent::__construct($context);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $paymentMethod = $this->getRequest()->getParam('payment_method');
        $subMethod = $this->getRequest()->getParam('sub_payment_method') ?? null;
        $collatedMethod = $this->getRequest()->getParam('collated_method') ?? null;
        $this->storeManager->setCurrentStore($this->getRequest()->getParam('store'));
        $quote = $this->checkoutSession->getQuote();
        $this->handlingApplier->updateHandlingFee($quote, $paymentMethod, $subMethod, $collatedMethod);
    }
}
