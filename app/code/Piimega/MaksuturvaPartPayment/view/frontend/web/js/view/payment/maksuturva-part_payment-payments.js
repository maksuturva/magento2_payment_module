define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'maksuturva_part_payment_payment',
                component: 'Svea_MaksuturvaPartPayment/js/view/payment/method-renderer/maksuturva-part_payment-method'
            }
        );
        return Component.extend({});
    }
);
