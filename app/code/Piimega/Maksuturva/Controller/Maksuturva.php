<?php
namespace Piimega\Maksuturva\Controller;

abstract class Maksuturva extends \Magento\Framework\App\Action\Action
{
    protected $_orderFactory;
    protected $_objectManager;
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

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
        array $data = []
    ) 
    {
    	parent::__construct($context);
        $this->_objectManager = $context->getObjectManager();
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutsession;
        $this->_maksuturvaHelper = $maksuturvaHelper;
    }

    protected function _createInvoice($order)
    {
        if (!$order->canInvoice() || (!$this->_scopeConfig->getValue('maksuturva_payment/maksuturva_config/generate_invoice'))) {
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
            $session = $this->_checkoutSession;
            $this->_order = $this->_orderFactory->create();
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    public function getConfigData($path)
    {
        return $this->_scopeConfig->getValue('maksuturva_payment/maksuturva_config/'.$path.'', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function disableQuote($order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(0)->setReservedOrderId($order->getIncrementId());
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
            $order = $this->_orderFactory->create()->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->setOrder('entity_id', 'DESC')
                ->getFirstItem();
            //set session data
            if($order->getId()){
                $this->setOrder($order);
                $this->_checkoutSession->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());
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
}
