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
                type: 'maksuturva_masterpass',
                component: 'Piimega_MaksuturvaMasterpass/js/view/payment/method-renderer/masterpass-renderer'
            }
        );
        return Component.extend({});
    }
);
