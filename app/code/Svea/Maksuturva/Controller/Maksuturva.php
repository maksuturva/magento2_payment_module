<?php
namespace Svea\Maksuturva\Controller;

use Magento\Framework\Api\SortOrder;
use Svea\Maksuturva\Model\Gateway\Exception;
use Svea\Maksuturva\Setup\UpgradeSchema;

abstract class Maksuturva extends \Magento\Framework\App\Action\Action
{
    protected $_orderFactory;
    protected $_customerSession;
    protected $_convertorFactory;
    protected $_scopeConfig;
    protected $quoteRepository;
    protected $_checkoutSession;
    protected $_maksuturvaHelper;
    protected $mandatoryFields = array(
        "pmt_action",
        "pmt_version",
        "pmt_id",
        "pmt_reference",
        "pmt_amount",
        "pmt_currency",
        "pmt_sellercosts",
        "pmt_paymentmethod",
        "pmt_escrow",
        "pmt_hash"
    );

    protected $_order;
    protected $orderFactory;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $sortOrderBuilder;
    protected $orderPaymentRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Svea\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        array $data = []
    ) 
    {
    	parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutsession;
        $this->_maksuturvaHelper = $maksuturvaHelper;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderFactory = $orderFactory;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    protected function _createInvoice($order)
    {
        // TODO: Check and test change below. Commented line is original. Hene 20.11.2019
        //if (!$order->canInvoice() || (!$this->_maksuturvaHelper->generateInvoiceAutomatically())) {
        if (!$order->canInvoice() || (!$this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/generate_invoice'))) {
            return false;
        }
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        if ($invoice->canCapture()) {
            $invoice->capture();
        }
        $invoice->save();
        $order->addRelatedObject($invoice);
        return $invoice;
    }

    public function getOrder()
    {
        if ($this->_order == null)
        {
            $this->_order = $this->_orderFactory->create();
            $this->_order->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
        }
        return $this->_order;
    }

    public function getConfigData($path)
    {
        return $this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/'.$path.'', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function disableQuote($order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(0)->setReservedOrderId($order->getIncrementId());
        $this->quoteRepository->save($quote);
        $this->_checkoutSession->setLastQuoteId($quote->getId());
    }

    public function activeQuote($order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId($order->getIncrementId());
        $this->quoteRepository->save($quote);
        $this->_checkoutSession->setLastQuoteId($quote->getId());
    }

    protected function setOrder(\Magento\Sales\Model\Order $order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * @param $paymentId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getOrderByPaymentId($paymentId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(UpgradeSchema::COLUMN_MAKSUTURVA_PMT_ID, $paymentId)
            ->create();

        $payments = $this->orderPaymentRepository->getList($searchCriteria)->getItems();

        if(empty($payments)) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Payment with id %1 not found', $paymentId));
        }

        $payment = reset($payments);
        $order = $this->orderRepository->get($payment->getParentId());

        return $order;
    }

    public function getLastedOrder()
    {
        if(!$this->_checkoutSession->getLastRealOrderId()){
            $quoteId = $this->_checkoutSession->getQuote()->getId();

            //create sort order
            $sortOrder = $this->sortOrderBuilder
                ->setField('entity_id')
                ->setDirection(SortOrder::SORT_DESC)
                ->create();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('quote_id', $quoteId, 'eq')
                ->addSortOrder($sortOrder)->create();



            $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
            if(is_array($orderList) && !empty($orderList)){
                $order = reset($orderList);
                //set session data
                if($order->getId()){
                    $this->setOrder($order);
                    $this->_checkoutSession->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());
                }
            }
        }

        return $this->getOrder();
    }

    public function validateReturnedOrder($order, $params)
    {
        //ensure the returned order as same as the loaded order
        if($this->_maksuturvaHelper->getPmtReferenceNumber($order->getIncrementId() + 100) == @$params['pmt_reference']){
            return true;
        }else{
            return false;
        }
    }

    protected function getHelper()
    {
        return $this->_maksuturvaHelper;
    }

    protected function getPayment()
    {
        $order = $this->getLastedOrder();
        if($order->getId() && $order instanceof \Magento\Sales\Model\Order){
           return $order->getPayment();
        }
    }
}
