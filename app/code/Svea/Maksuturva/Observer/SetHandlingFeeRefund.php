<?php
namespace Svea\Maksuturva\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetHandlingFeeRefund implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $order->setRefundedHandlingFee($creditmemo->getBaseHandlingFee());
    }
}