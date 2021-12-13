/**
 *  Copyright © Vaimo Group. All rights reserved.
 *  See LICENSE_VAIMO.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
], function (ko, Component, quote, totals) {
    'use strict';

    return Component.extend({
        defaults: {
            isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
            template: 'Svea_Maksuturva/checkout/summary/handling',
        },
        totals: quote.getTotals(),
        isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,
        isDisplayed: function () {
            return this.getPureValue() > 0;
        },
        getPureValue: function () {
            var price = 0,
                handlingSegment = totals.getSegment('handling_fee'),
                handlingValue = handlingSegment ? totals.getSegment('handling_fee').value : null;
            if (this.totals() && handlingValue) {
                price = parseFloat(handlingValue);
            }
            return price;
        },
        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});
