define(
    [
        'ko',
        'Svea_Maksuturva/js/view/payment/method-renderer/maksuturva_payment',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-totals',
    ],
    function (ko, Component, quote, fullscreenLoader, getTotalsAction) {
        'use strict';

        const method_code = 'maksuturva_collated_payment';
        const later_method_code = 'pay_later';
        const other_method_code = 'pay_now_other';
        const bank_method_code = 'pay_now_bank';
        return Component.extend({

            payments: window.checkoutConfig.payment[method_code]['methods'],
            titles: window.checkoutConfig.payment[method_code]['titles'],
            title_pay_later: window.checkoutConfig.payment[later_method_code]['title'],
            payments_pay_later: window.checkoutConfig.payment[later_method_code]['methods'],
            title_pay_other: window.checkoutConfig.payment[other_method_code]['title'],
            payments_pay_other: window.checkoutConfig.payment[other_method_code]['methods'],
            title_pay_bank: window.checkoutConfig.payment[bank_method_code]['title'],
            payments_pay_bank: window.checkoutConfig.payment[bank_method_code]['methods'],


            defaults: {
                template: window.checkoutConfig.payment[method_code]['template'],
            },

            getCode: function() {
                return method_code;
            },
            getLaterCode: function() {
                return later_method_code;
            },
            getOtherCode: function() {
                return other_method_code;
            },
            getBankCode: function() {
                return bank_method_code;
            },
            selectSubMethod: function (data, event){
                this.selectedPayment(event.target.value);
                if (data.parentMethod) {
                    this.updateTotals('maksuturva_collated_payment', data.parentMethod);
                }
                return true;
            }
        });
    }
);
