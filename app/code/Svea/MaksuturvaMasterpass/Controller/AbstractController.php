<?php
namespace Svea\MaksuturvaMasterpass\Controller;

abstract class AbstractController extends \Svea\Maksuturva\Controller\Maksuturva
{
    protected $masterpass;
    protected $checkoutHelper;
    protected $_customerSession;

    protected $authMandatoryFields = array(
        "pmt_version",
        "pmt_id",
        "pmt_reference",
        "pmt_amount",
        "pmt_currency",
        "pmt_paymenturl"
    );

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Svea\MaksuturvaMasterpass\Helper\Data $maksuturvaHelper,
        \Svea\MaksuturvaMasterpass\Model\Masterpass $masterpass,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        parent::__construct($context, $orderFactory, $scopeConfig, $quoteRepository, $checkoutsession, $maksuturvaHelper, $orderRepository, $searchCriteriaBuilder, $sortOrderBuilder, $orderPaymentRepository);
        $this->masterpass = $masterpass;
        $this->checkoutHelper = $checkoutHelper;
        $this->_customerSession = $customerSession;
    }

    abstract function execute();

    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    protected function getPaymentMethod()
    {
        return $this->masterpass;
    }

    protected function getGateWay()
    {
        return $this->getPaymentMethod()->getGateway();
    }

    protected function canMasterpassCheckout()
    {
        $isValid = true;
        $checkoutHelper = $this->checkoutHelper;
        if (!$checkoutHelper->canOnepageCheckout()) {
            $this->messageManager->addError(__('One-page checkout is turned off.'));
            $this->_redirect('checkout/cart');
            $isValid = false;
        }

        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            $this->_redirect('checkout/cart');
            $isValid = false;
        }

        if (!$this->_customerSession->isLoggedIn() && !$checkoutHelper->isAllowedGuestCheckout($quote)) {
            $this->messageManager->addError(__('Guest checkout is disabled.'));
            $this->_redirect('checkout/cart');
            $isValid = false;
        }
        return $isValid;
    }
}