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

    public function checkPaymentStatusPoller(){
        $this->helper->sveaLoggerInfo("Payment status cron job short triggered.");
        $this->checkPaymentStatus("-30 minutes", "-6 hours");
    }

    public function checkPaymentStatusInShortTime(){
        $this->helper->sveaLoggerDebug("Payment status cron job short triggered.");
        $this->checkPaymentStatus("-1 minutes", "-4 hours");
    }

    public function checkPaymentStatusInLongTime(){
        $this->helper->sveaLoggerDebug("Payment status cron job long triggered.");
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
        
        $this->helper->sveaLoggerInfo("Finding Pending orders to query between " . str($to) . " to " . str($from));
        
        $orderCollection = $this->_orderCollectionFactory->create()
           ->join(array('payment' => 'sales_order_payment'), 'main_table.entity_id=parent_id', 'method')
           ->addFieldToFilter('status', "pending")
           ->addFieldToFilter('payment.method', array('like' => 'maksuturva_%'))
           ->addAttributeToFilter('created_at', array('gteq' => $from->format('Y-m-d H:i:s')));
           ->addAttributeToFilter('created_at', array('lt' => $to->format('Y-m-d H:i:s')));

        $this->helper->sveaLoggerInfo("Payment status cron job found " . $orderCollection->count() . " orders to be checked from Svea Payments.");

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

        $this->helper->sveaLoggerInfo("Payment status cron job finished.");
        $this->_localeResolver->revert();
    }
}