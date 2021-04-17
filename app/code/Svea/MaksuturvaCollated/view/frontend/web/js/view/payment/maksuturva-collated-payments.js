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
                type: 'maksuturva_collated_payment',
                component: 'Svea_MaksuturvaCollated/js/view/payment/method-renderer/maksuturva-collated-method'
            }
        );
        return Component.extend({});
    }
);
