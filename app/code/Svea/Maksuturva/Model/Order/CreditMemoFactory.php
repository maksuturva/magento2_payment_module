<?php
namespace Svea\Maksuturva\Model\Order;

use Magento\Sales\Model\Order\Creditmemo;

class CreditMemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * Initialize creditmemo state based on requested parameters
     *
     * @param Creditmemo $creditmemo
     * @param array $data
     * @return void
     */
    protected function initData($creditmemo, $data)
    {
        parent::initData($creditmemo, $data);

        if (isset($data['handling_fee'])) {
            $creditmemo->setBaseHandlingFee($data['handling_fee']);
            $creditmemo->setHandlingFee($data['handling_fee']);
        }
    }
}
