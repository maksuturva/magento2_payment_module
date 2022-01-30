define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-totals',
    ],
    function ($, quote, fullScreenLoader, getTotalsAction) {
        'use strict';
        return function (paymentMethod) {
            quote.paymentMethod(paymentMethod);

            fullScreenLoader.startLoader();

            const extension_attributes = paymentMethod.extension_attributes || null;
            const sub_payment_method = extension_attributes && extension_attributes.maksuturva_preselected_payment_method
                ? extension_attributes.maksuturva_preselected_payment_method
                : null;
            const collated_method = extension_attributes && extension_attributes.collated_method
                ? extension_attributes.collated_method
                : null;

            let data = {
                payment_method: paymentMethod.method,
                sub_payment_method: sub_payment_method,
                collated_method: collated_method
            };

            $.ajax('/maksuturva/checkout/applyPaymentMethod', {
                data: data,
                complete: function () {
                    getTotalsAction([]);
                    fullScreenLoader.stopLoader();
                }
            });
        }
    }
);
