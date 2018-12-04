/**
 * @author aakimov
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Piimega_MaksuturvaMasterpass/js/checkout/model/new-masterpass-address',
    'Magento_Checkout/js/action/select-billing-address'
], function ($, wrapper, NewMasterpassAddress,selectBillingAddress) {
    'use strict';

    return function (targetModule) {
        var masterpassData = window.checkoutConfig.masterpassData;
        var getAddressItems = targetModule.getAddressItems;
        var getAddressItemsWrapper = wrapper.wrap(getAddressItems, function (originalAction) {
            var result = originalAction();
            if (Object.keys(masterpassData).length) {
                $.each(masterpassData.addresses, function (key, item) {
                    if(key == "shipping"){
                        result.push(new NewMasterpassAddress(item));
                    }else if(key == "billing"){
                        selectBillingAddress(new NewMasterpassAddress(item));
                    }
                });
            }
            return result;
        });
        targetModule.getAddressItems = getAddressItemsWrapper;
        return targetModule;
    };
});

