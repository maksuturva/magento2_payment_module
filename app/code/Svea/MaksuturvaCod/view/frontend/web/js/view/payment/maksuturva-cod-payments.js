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
                type: 'maksuturva_cod_payment',
                component: 'Svea_MaksuturvaCod/js/view/payment/method-renderer/maksuturva-cod-method'
            }
        );
        return Component.extend({});
    }
);
