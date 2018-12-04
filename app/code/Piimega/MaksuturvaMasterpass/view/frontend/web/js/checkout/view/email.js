/**
 * @author aakimov
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        'jquery',
        'mage/utils/wrapper',
        'Magento_Checkout/js/view/form/element/email',
        'Magento_Checkout/js/model/quote'
    ], function ($, wrapper, uiComponent,quote) {
        'use strict';
        var masterpassData = window.checkoutConfig.masterpassData;

        return uiComponent.extend({
            defaults: {
                template : 'Piimega_MaksuturvaMasterpass/checkout/form/element/email'
            },

            getQuoteEmail : function(){
                var email = "";
                if (Object.keys(masterpassData).length) {
                    $.each(masterpassData.addresses, function (key, item) {
                        email = item.email;
                        //set the masterpass email as quote guestEmail
                        quote.guestEmail = email;
                        return false;
                    });
                }
                return email;
            },

            isEditable : function(){
                var result = true;
                if (Object.keys(masterpassData).length) {
                    //hide login form if this is masterpass payment
                    result = false;
                }
                return result;
            }
        });
    }
);