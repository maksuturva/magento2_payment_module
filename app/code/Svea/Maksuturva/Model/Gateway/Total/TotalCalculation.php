<?php

namespace Svea\Maksuturva\Model\Gateway\Total;

use Magento\Sales\Api\Data\OrderInterface;

class TotalCalculation
{
    /**
     * Get order total including tax but without shipping price and tax and handling fee.
     * Discount are included in the total
     *
     * @param OrderInterface $order
     * @return float
     */
    public function getProductsTotal($order)
    {
        return $order->getBaseGrandTotal()
            - $order->getBaseShippingAmount()
            - $order->getBaseShippingTaxAmount()
            - $order->getHandlingFee();
    }

    /**
     * Get tax compensated discount amount regardless of Magento configuration
     *
     * @param OrderInterface $order
     * @return float
     */
    public function getDiscountAmount($order)
    {
        // By tax compensated discount we mean the value which is
        // actually subtracted from the total to get the correct total for
        // the order, and not the value provided by Magentos base_discount_amount
        // because it may change depending on the configuration at which
        // point the discounts are calculated (before / after tax)

        // Start from productsTotal which has only the real prices for products
        // discounts etc. applied
        return  $this->getProductsTotal($order)

            // Subtract the products normal price from the total to get negative discount amount.
            - $order->getBaseSubtotalInclTax()

            // Add the gift card discount back because it is added as a separate discount row
            + $this->getGiftCardAmount($order);
    }

    /**
     * Get the gift card amount from order as a positive number
     *
     * @param OrderInterface $order
     *
     * @return float
     */
    private function getGiftCardAmount($order)
    {
        $orderData = $order->getData();

        if (isset($orderData["base_gift_cards_amount"]) && $orderData["base_gift_cards_amount"] != 0) {
            return abs((float)$orderData["base_gift_cards_amount"]);
        }

        return 0.0;
    }
}
