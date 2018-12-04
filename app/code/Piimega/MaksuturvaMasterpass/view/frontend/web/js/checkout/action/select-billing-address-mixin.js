define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Piimega_MaksuturvaMasterpass/js/checkout/model/new-masterpass-address'
], function ($, wrapper, quote, NewMasterpassAddress) {
    'use strict';

    return function (selectBillingAddressAction) {
        return wrapper.wrap(selectBillingAddressAction, function (originalAction, address) {
            var masterpassData = window.checkoutConfig.masterpassData;
            if (Object.keys(masterpassData).length) {
                $.each(masterpassData.addresses, function (key, item) {
                    if(key == "billing"){
                        address = new NewMasterpassAddress(item);
                        return false;
                    }
                });
            }
            return originalAction(address);
        });
    };
});