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
                type: 'maksuturva_generic_payment',
                component: 'Svea_MaksuturvaGeneric/js/view/payment/method-renderer/maksuturva-generic-method'
            }
        );
        return Component.extend({});
    }
);
