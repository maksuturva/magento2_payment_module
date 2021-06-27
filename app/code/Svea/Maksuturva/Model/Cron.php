<?php
namespace Svea\Maksuturva\Model;
class Cron
{
    protected $_scopeConfig;
    protected $_orderCollectionFactory;
    protected $_localeResolver;
    protected $_localeDate;
    protected $registry;
    protected $helper;

    function __construct(
        \Svea\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Registry $registry
    )
    {
        $this->helper = $maksuturvaHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_localeResolver = $localeResolver;
        $this->_localeDate = $localeDate;
        $this->registry = $registry;
    }

    public function checkPaymentStatusPoller() {
        try {
            $this->checkPaymentStatus("-30 minutes", "-24 hours");    
        } catch (Exception $e) {
            $this->helper->sveaLoggerError("Payment status automatic query failed, reason " . $e->getMessage());
        }
    }

    public function checkPaymentStatusInShortTime(){
        $this->checkPaymentStatus("-1 minutes", "-24 hours");
    }

    public function checkPaymentStatusInLongTime(){
        $this->checkPaymentStatus("-1 minutes", "-1 weeks");
    }

    public function checkPaymentStatus($starttime = "-1 minutes", $lookback = "-4 hours")
    {
        if (!$this->_scopeConfig->isSetFlag('maksuturva_config/maksuturva_payment/cron_active') && !$this->registry->registry('run_cron_manually')) {
            return;
        }
        $this->_localeResolver->emulate(0);

        $from = $this->_localeDate->date();
        $from->modify($lookback);

        $to = $this->_localeDate->date();
        $to->modify($starttime);
        
        $this->helper->sveaLoggerInfo("Payment status job finding 'Pending' orders between " . $to->format('Y-m-d H:i:s') . " to " . $from->format('Y-m-d H:i:s') );
        
        $orderCollection = $this->_orderCollectionFactory->create()
           ->join(array('payment' => 'sales_order_payment'), 'main_table.entity_id=parent_id', 'method')
           ->addFieldToFilter('status', "pending")
           ->addFieldToFilter('payment.method', array('like' => 'maksuturva_%'))
           ->addAttributeToFilter('created_at', array('gteq' => $from->format('Y-m-d H:i:s')))
           ->addAttributeToFilter('created_at', array('lt' => $to->format('Y-m-d H:i:s')));

        if ($orderCollection->count()>0) {
            $this->helper->sveaLoggerInfo("Payment status job found " . $orderCollection->count() . " orders to be checked from Svea Payments.");
        } else {
            $this->helper->sveaLoggerInfo("Payment status job found no 'Pending' orders to be checked.");
        }
        foreach ($orderCollection as $order) {
            $model = $order->getPayment()->getMethodInstance();
            $this->helper->sveaLoggerInfo("Checking order " . $order->getIncrementId() . " created at " . $order->getCreatedAt());
            $implementation = $model->getGatewayImplementation();
            if ($implementation != NULL) 
            {
                $implementation->setOrder($order);
                $config = $model->getConfigs();
                $data = array('pmtq_keygeneration' => $config['keyversion']);

                try {
                    $response = $implementation->statusQuery($data);
                    $result = $implementation->ProcessStatusQueryResult($response);
                    $this->helper->sveaLoggerInfo("Order " . $order->getIncrementId() . " query status " . $result['message']);
                } catch (\Exception $e) 
                {
                    $this->helper->sveaLoggerError("Order " . $order->getIncrementId() . " status query exception: " . $e->getMessage());
                }
            }
        }

        $this->helper->sveaLoggerInfo("Payment status job finished.");
        $this->_localeResolver->revert();
    }
}