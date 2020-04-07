<?php
namespace Svea\Maksuturva\Controller;

use Magento\Framework\Api\SortOrder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Svea\Maksuturva\Model\Gateway\Exception;

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
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    protected function _createInvoice($order)
    {
        //if (!$order->canInvoice() || (!$this->_maksuturvaHelper->generateInvoiceAutomatically())) {
        if (!$order->canInvoice() || (!$this->_scopeConfig->getValue('maksuturva_config/maksuturva_payment/generate_invoice'))) {
            return false;
        }

        /**
         * Add transaction info to payment
         */
        $payment = $order->getPayment();
        $payment->setTransactionId(
            \json_decode($order->getPayment()->getAdditionalData(), true)['maksuturva_transaction_id']
            )
            ->setTransactionClosed(0);
        $order->save();

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();

        /**
         * Create transaction
         */
        $payment->setCreatedInvoice($invoice);
        $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, $invoice, true);

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
