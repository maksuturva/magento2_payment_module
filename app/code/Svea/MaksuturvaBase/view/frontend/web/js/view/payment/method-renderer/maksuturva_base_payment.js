define(
    [
        'ko',
        'Svea_Maksuturva/js/view/payment/method-renderer/maksuturva_payment'
    ],
    function (ko, Component) {
        'use strict';

        const method_code = 'maksuturva_base_payment';
        var selecedMethod = ko.observable(window.checkoutConfig.payment[method_code]['defaultPaymentMethod']);

        return Component.extend({

            payments: window.checkoutConfig.payment[method_code]['methods'],

            defaults: {
                template: window.checkoutConfig.payment[method_code]['template'],
            },

            selectedPayment: selecedMethod,

            getCode: function() {
                return method_code;
            }
        });
    }
);
