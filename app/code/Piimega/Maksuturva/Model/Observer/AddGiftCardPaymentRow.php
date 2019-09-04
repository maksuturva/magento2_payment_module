<?php
/**
 *  Copyright © Vaimo Group. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Piimega\Maksuturva\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddGiftCardPaymentRow implements ObserverInterface
{
    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $transportObject = $observer->getEvent()->getTransportObject();

        $order = $transportObject->getOrder();
        $options = $transportObject->getOptions();

        $orderData = $order->getData();

        if (isset($orderData["base_gift_cards_amount"]) && $orderData["base_gift_cards_amount"] != 0) {
            $discount = (float)$orderData["base_gift_cards_amount"];

            $row = array(
                'pmt_row_name' => "Gift cards",
                'pmt_row_desc' => "Gift cards",
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date("d.m.Y"),
                'pmt_row_price_net' =>
                    str_replace(
                        '.',
                        ',',
                        sprintf(
                            "%.2f",
                            $discount
                        )
                    ),
                'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", 0)),
                'pmt_row_discountpercentage' => "0,00",
                'pmt_row_type' => 6, // discounts
            );

            $totalAmount = $this->getTotalAmount($options);
            $totalAmount = $discount > 0 ? ($totalAmount - $discount) : ($totalAmount + $discount);

            $options["pmt_amount"] = str_replace('.', ',', sprintf("%.2f", $totalAmount));

            array_push($options["pmt_rows_data"], $row);
            $options["pmt_rows"] = count($options["pmt_rows_data"]);

            $transportObject->setOptions($options);
        }
    }

    /**
     * @param array $options
     *
     * @return float
     */
    protected function getTotalAmount(array $options)
    {
        return (float)str_replace(',', '.', $options["pmt_amount"]);
    }
}
