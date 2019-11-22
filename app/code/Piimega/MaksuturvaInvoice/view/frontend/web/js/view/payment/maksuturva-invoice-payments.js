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
                type: 'maksuturva_invoice_payment',
                component: 'Svea_MaksuturvaInvoice/js/view/payment/method-renderer/maksuturva-invoice-method'
            }
        );
        return Component.extend({});
    }
);
