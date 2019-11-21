<?php
namespace Piimega\Maksuturva\Model;
class Cron
{
    protected $_scopeConfig;
    protected $_orderCollectionFactory;
    protected $_localeResolver;
    protected $_localeDate;
    protected $registry;
    protected $helper;

    function __construct(
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
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

    public function checkPaymentStatusInShortTime(){
        $this->checkPaymentStatus("-2 hours");
    }

    public function checkPaymentStatusInLongTime(){
        $this->checkPaymentStatus("-2 weeks");
    }

    public function checkPaymentStatus($lookback = "-2 hours")
    {
        if (!$this->_scopeConfig->isSetFlag('maksuturva_config/maksuturva_payment/cron_active') && !$this->registry->registry('run_cron_manually')) {
            return;
        }

        $this->_localeResolver->emulate(0);

        $this->helper->maksuturvaLogger("starting maksuturva order status check");

        $from = $this->_localeDate->date();
        $from->modify($lookback);

        $to = $this->_localeDate->date();
        $to->modify("-15 minutes");

        $orderCollection = $this->_orderCollectionFactory->create();
        $resource = $orderCollection->getResource();
        $con = $resource->getConnection();

        $q1 = "SELECT `main_table`.* FROM `sales_order` AS `main_table` WHERE (`created_at` >= '2017-11-20 14:02:36')";
        $q1 = "SELECT * FROM sales_order WHERE created_at >= '2017-11-20 14:42:13'";
        $stmnt = $con->query($q1);
       // $stmnt = $con->execute();
        $result = $stmnt->fetchAll();


        $orderCollection = $this->_orderCollectionFactory->create()
            //->join(array('payment' => 'sales_order_payment'), 'main_table.entity_id=parent_id', 'method')
           // ->addFieldToFilter('main_table.status', "pending")
            //->addFieldToFilter('payment.method', array('like' => 'maksuturva_%'))
            ->addAttributeToFilter('created_at', array('gteq' => $from->format('Y-m-d H:i:s')));
           // ->addAttributeToFilter('created_at', array('lt' => $to->format('Y-m-d H:i:s')));

        $this->helper->maksuturvaLogger("found " . $orderCollection->count() . " orders to check");

        foreach ($orderCollection as $order) {
            $model = $order->getPayment()->getMethodInstance();
            $this->helper->maksuturvaLogger("checking " . $order->getIncrementId());
            $implementation = $model->getGatewayImplementation();
            if ($implementation != NULL) 
            {
                $implementation->setOrder($order);
                $config = $model->getConfigs();
                $data = array('pmtq_keygeneration' => $config['keyversion']);

                try {
                    $response = $implementation->statusQuery($data);
                    $result = $implementation->ProcessStatusQueryResult($response);
                    $this->helper->maksuturvaLogger($result['message']);
                } catch (\Exception $e) 
                {
                    $this->helper->maksuturvaLogger("Order status query exception: " . $e->getMessage());
                }
            }
        }

        $this->helper->maksuturvaLogger("finished order status check");
        $this->_localeResolver->revert();
    }
}