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
            $this->helper->sveaLoggerInfo("Scheduled payment status query from 15 min to 1 week.");
            $this->checkPaymentStatus("-15 minutes", "-1 week");    
        } catch (Exception $e) {
            $this->helper->sveaLoggerError("Payment status automatic query failed, reason " . $e->getMessage());
        }
    }

    public function checkPaymentStatusInShortTime(){
        $this->checkPaymentStatus("-1 minutes", "-1 day");
    }

    public function checkPaymentStatusInLongTime(){
        $this->checkPaymentStatus("-1 minutes", "-1 weeks");
    }

    public function checkPaymentStatus($starttime, $lookback)
    {
        if (!$this->_scopeConfig->isSetFlag('maksuturva_config/maksuturva_payment/cron_active') && !$this->registry->registry('run_cron_manually')) {
            return;
        }

        $this->_localeResolver->emulate(0);

        $to = $this->_localeDate->date();
        $to->modify($starttime);

        $from = $this->_localeDate->date();
        $from->modify($lookback);
        
        $this->helper->sveaLoggerInfo("Payment status job finding 'Pending' orders between " . 
            $from->format('Y-m-d H:i:s') . " to " . $to->format('Y-m-d H:i:s') );
        
        $orderCollection = $this->_orderCollectionFactory->create()
           ->join(array('payment' => 'sales_order_payment'), 'main_table.entity_id=parent_id', 'method')
           ->addFieldToFilter('status', "pending")
           ->addFieldToFilter('payment.method', array('like' => 'maksuturva_%'))
           ->addAttributeToFilter('created_at', array('gteq' => $from->format('Y-m-d H:i:s')))
           ->addAttributeToFilter('created_at', array('lt' => $to->format('Y-m-d H:i:s')));

        if ($orderCollection->count()>0) {
            $this->helper->sveaLoggerInfo("Payment status job found " . $orderCollection->count() . " pending orders.");
        } else {
            $this->helper->sveaLoggerInfo("Payment status job found no 'Pending' orders to be checked.");
        }
        foreach ($orderCollection as $order) {
            $model = $order->getPayment()->getMethodInstance();
            $this->helper->sveaLoggerInfo("Checking order " . $order->getIncrementId() . " created at " . $order->getCreatedAt() . ", updated at " . $order->getUpdatedAt());
            $checkrule = $this->is_time_to_check( $order->getCreatedAt(),  $order->getUpdatedAt());

            if ($checkrule>0)
            {
                $this->helper->sveaLoggerInfo("Order " . $order->getIncrementId() . " check rule " . strval($checkrule) );
                $implementation = $model->getGatewayImplementation();
                if ($implementation != NULL) 
                {
                    $implementation->setOrder($order);
                    $config = $model->getConfigs();
                    $data = array('pmtq_keygeneration' => $config['keyversion']);

                    try {
                        $response = $implementation->statusQuery($data);
                        if (is_array($response)) {
                            $result = $implementation->ProcessStatusQueryResult($response);
                            $this->helper->sveaLoggerInfo("Order " . $order->getIncrementId() . " query status " . $result['message']);
                        }
                        
                    } catch (\Exception $e) 
                    {
                        $this->helper->sveaLoggerError("Order " . $order->getIncrementId() . " status query exception: " . $e->getMessage());
                    }
                }
            } else {
                $this->helper->sveaLoggerInfo("Order " . $order->getIncrementId() . " does not match check time window.");
            }
        }

        $this->helper->sveaLoggerInfo("Scheduled payment status query job finished.");
        $this->_localeResolver->revert();
    }


    /**
	 * 
	 * Payment status query window check. Ideally this would be in the database query but this is for
     * WC module compatibility reasons.
	 * 
	 */
	private function is_time_to_check($payment_date_added, $payment_date_updated)
	{
		$now_time = strtotime(date('Y-m-d H:i:s'));
		
		$create_diff = $now_time - strtotime($payment_date_added);
		/* if there is no 'updated date', so do status query if order is created max 7 days ago */
		if (is_null($payment_date_updated) && $this->in_range($create_diff, 0, 168*3600)) {
			return 4;
		}
		$update_diff = $now_time - strtotime($payment_date_updated);

		$checkrule = 0;
        if ($this->in_range($create_diff, 60, 2*3600) && $update_diff > 60) {
		//if ($this->in_range($create_diff, 5*60, 2*3600) && $update_diff > 20*60) {
			$checkrule = 1;
		}
		if ($this->in_range($create_diff, 2*3600, 24*3600) && $update_diff > 2*3600) {
			$checkrule = 2;
		} 
		// 168 hours = 7 days. No older than 7 days allowed.
		if ($create_diff < 168*3600 && $update_diff > 12 * 3600) {
			$checkrule = 3;
		}

        return $checkrule;
	}

	/**
	 * Determines if $number is between $min and $max
	 *
	 * @param  integer  $number     The number to test
	 * @param  integer  $min        The minimum value in the range
	 * @param  integer  $max        The maximum value in the range
	 * @param  boolean  $inclusive  Whether the range should be inclusive or not
	 * @return boolean              Whether the number was in the range
	 */
	private function in_range($number, $min, $max, $inclusive = FALSE)
	{
		$number = intval($number);
		$min = intval($min);
		$max = intval($max);

		return $inclusive
			? ($number >= $min && $number <= $max)
			: ($number > $min && $number < $max);
	}

}