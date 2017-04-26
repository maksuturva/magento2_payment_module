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
                type: 'maksuturva_card_payment',
                component: 'Piimega_MaksuturvaCard/js/view/payment/method-renderer/maksuturva-card-method'
            }
        );
        return Component.extend({});
    }
);
