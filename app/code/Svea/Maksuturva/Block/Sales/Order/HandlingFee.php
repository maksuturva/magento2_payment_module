<?php
namespace Svea\Maksuturva\Block\Sales\Order;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Context;

class HandlingFee extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var DataObjectFactory
     */
    private $objectFactory;

    public function __construct(
        Context $context,
        DataObjectFactory $objectFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectFactory = $objectFactory;
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $order = $this->getOrder();

        if ($order->getHandlingFee() > 0) {
            $handlingFee = $this->objectFactory->create();
            $handlingFee->setData(
                [
                    'code' => 'handling_fee',
                    'strong' => false,
                    'value' => $order->getHandlingFee(),
                    'base_value' => $order->getHandlingFee(),
                    'label' => \__('Handling Fee'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($handlingFee, 'grand_total');
        }

        return $this;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }
}

