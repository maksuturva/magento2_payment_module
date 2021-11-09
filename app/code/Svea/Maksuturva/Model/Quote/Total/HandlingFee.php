<?php
namespace Svea\Maksuturva\Model\Quote\Total;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Svea\Maksuturva\Model\Config\Config;
use Svea\Maksuturva\Model\ResourceModel\HandlingFeeResource;

class HandlingFee extends AbstractTotal
{
    const CODE = 'handling_fee';

    /**
     * @var Config
     */
    private $configProvider;

    /**
     * HandlingFee constructor.
     * @param Config $configProvider
     */
    public function __construct(
        Config $configProvider
    ) {
        $this->configProvider = $configProvider;
        $this->setCode(self::CODE);
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (empty($items)) {
            return $this;
        }

        $handlingFee = $this->calculateHandlingFee($quote, $total);

        $quote->setData(HandlingFeeResource::HANDLING_FEE, $handlingFee);
        $quote->setData(HandlingFeeResource::BASE_HANDLING_FEE, $handlingFee);

        $total->setData(HandlingFeeResource::HANDLING_FEE, $handlingFee);
        $total->setData(HandlingFeeResource::BASE_HANDLING_FEE, $handlingFee);
        $total->setTotalAmount(self::CODE, $handlingFee);
        $total->setBaseTotalAmount(self::CODE, $handlingFee);

        return $this;
    }

    /**
     * @param CartInterface $quote
     * @param Total $total
     * @return float
     */
    private function calculateHandlingFee(CartInterface $quote, Total $total)
    {
        $paymentCode = $quote->getPayment()->getMethod();
        if ($collatedMethod = $quote->getPayment()->getAdditionalInformation('collated_method')) {
            $paymentCode = $collatedMethod;
        }
        $handlingFeeConfig = $this->configProvider->getHandlingFee();
        foreach ($handlingFeeConfig as $key => $fee) {
            if ($key == $paymentCode) {
                return $fee;
            }
        }

        return 0;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $this->calculateHandlingFee($quote, $total)
        ];
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return \__('Handling Fee');
    }
}