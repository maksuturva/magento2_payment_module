/**
 * @author aakimov
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        'jquery',
        'mage/utils/wrapper',
        'Magento_Checkout/js/model/quote'
    ], function ($, wrapper, quote) {
        'use strict';
        return function (targetModule) {
            var masterpassData = window.checkoutConfig.masterpassData;
            var isCustomerLoggedIn = targetModule.isCustomerLoggedIn;
            var isCustomerLoggedInWrapper = wrapper.wrap(isCustomerLoggedIn, function (originalAction) {
                var result = originalAction();
                if (Object.keys(masterpassData).length) {
                    //hide login form if this is masterpass payment
                    result = true;
                }
                return result;
            });
            targetModule.isCustomerLoggedIn = isCustomerLoggedInWrapper;


            var defaults = targetModule.defaults;
            var defaultsWrapper = wrapper.wrap(defaults, function (originalAction) {
                var result = originalAction();
                if (Object.keys(masterpassData).length) {
                    result.template = 'Piimega_MaksuturvaMasterpass/checkout/form/element/email';
                }
                return result;
            });
            targetModule.defaults = defaultsWrapper;


            var getQuoteEmail = function(){
                var email = "";
                if (Object.keys(masterpassData).length) {
                    $.each(masterpassData.addresses, function (key, item) {
                        email = item.email;
                        quote.guestEmail = email;
                        return false;
                    });
                }
                return email;
            }
            targetModule.getQuoteEmail = getQuoteEmail;
            return targetModule;
        };
    }
);