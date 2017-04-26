define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'mage/validation'
    ],
    function ($,Component, selectPaymentMethodAction,quote, checkoutData, urlBuilder, url,customer, additionalValidators, placeOrderAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Piimega_MaksuturvaCod/payment/maksuturva-cod-form',
            },

            getCode: function() {
                return 'maksuturva_cod_payment';
            },

            isActive: function() {
                return true;
            },
            getInstructions: function () {
                return '';
            },

            getData: function () {
                if(jQuery('#maksuturva_cod_payment_method').length && jQuery('#maksuturva_cod_payment_method').val()){
                    var extension_attributes = {maksuturva_preselected_payment_method : jQuery('#maksuturva_cod_payment_method').val()};
                }else if(jQuery('input[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').length && jQuery('input[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').val()){
                    var extension_attributes = {maksuturva_preselected_payment_method : jQuery('input[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').val()};
                }else if(jQuery('select[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').length && jQuery('select[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').val()){
                    var extension_attributes = {maksuturva_preselected_payment_method : jQuery('select[name="payment[maksuturva_cod_preselected_payment_method]"]:checked').val()};
                }else{
                    var extension_attributes = {maksuturva_preselected_payment_method : window.checkoutConfig.payment.maksuturva_cod_payment.defaultPaymentMethod};
                }
                return {
                    "method": this.item.method,
                    "extension_attributes": extension_attributes
                };

            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            afterPlaceOrder: function () {
                window.location.href = url.build('maksuturva/index/redirect/?timestamp='+ new Date().getTime() +'');
            },

            validate: function () {
                var form = 'form[data-role=maksuturva_cod_payment-form]';
                return $(form).validation() && $(form).validation('isValid');
            },

            getPreSelectedBank: function(){
                var html = "";
                if(window.checkoutConfig.payment.maksuturva_cod_payment.html != "undefined"){
                    html = window.checkoutConfig.payment.maksuturva_cod_payment.html;
                }
                return html;
            }
        });
    }
);
