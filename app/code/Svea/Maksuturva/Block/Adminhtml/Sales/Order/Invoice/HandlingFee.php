<?php
namespace Svea\Maksuturva\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;

class HandlingFee extends \Magento\Framework\View\Element\Template
{
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

    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
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
        $invoice = $parent->getInvoice();
        $handlingFee = $this->objectFactory->create();
        $handlingFee->setData(
            [
                'code' => 'handling_fee',
                'strong' => false,
                'value' => $invoice->getHandlingFee(),
                'base_value' => $invoice->getHandlingFee(),
                'label' => __('Handling Fee'),
            ]
        );
        $this->getInvoice()->setGrandTotal($invoice->getHandlingFee());
        $parent->addTotalBefore($handlingFee, 'grand_total');

        return $this;
    }
}
