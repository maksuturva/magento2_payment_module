<?php
namespace Svea\Maksuturva\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;

class HandlingFee extends \Magento\Framework\View\Element\Template
{
    /**
     * @var DataObject
     */
    private $source;

    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @var DataObjectFactory
     */
    private $objectFactory;

    /**
     * HandlingFee constructor.
     * @param Template\Context $context
     * @param DataObjectFactory $objectFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        DataObjectFactory $objectFactory,
        array $data = []
    ) {
        $this->objectFactory = $objectFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function getStore()
    {
        return $this->order->getStore();
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getCreditMemo()
    {
        return $this->getParentBlock()->getCreditMemo();
    }

    /**
     * @return mixed
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return mixed
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->source = $parent->getSource();
        $this->order = $parent->getOrder();
        $handlingFee = $this->objectFactory->create();
        $handlingFee->setData(
            [
                'code' => 'handling_fee',
                'strong' => false,
                'value' => $this->source->getHandlingFee(),
                'base_value' => $this->source->getHandlingFee(),
                'label' => __('Handling Fee'),
                'block_name' => 'handling_fee'
            ]
        );
        $this->getCreditMemo()->setGrandTotal($this->source->getHandlingFee());
        $parent->addTotalBefore($handlingFee, 'grand_total');

        return $this;
    }

    public function getHandlingFee()
    {
        return $this->source->getHandlingFee();
    }

    public function getFormattedHandlingFee()
    {
        return $this->order->getBaseCurrency()->format(
            (float) $this->source->getHandlingFee(), null, false
        );
    }
}
