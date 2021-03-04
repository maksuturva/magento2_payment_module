define(
    [
        'ko',
        'Svea_Maksuturva/js/view/payment/method-renderer/maksuturva_payment'
    ],
    function (ko, Component) {
        'use strict';

        const method_code = 'maksuturva_part_payment_payment';
        var selectedMethod = ko.observable(window.checkoutConfig.payment[method_code]['defaultPaymentMethod']);

        return Component.extend({

            payments: window.checkoutConfig.payment[method_code]['methods'],

            defaults: {
                template: window.checkoutConfig.payment[method_code]['template'],
            },

            selectedPayment: selectedMethod,

            getCode: function() {
                return method_code;
            }
        });
    }
);
