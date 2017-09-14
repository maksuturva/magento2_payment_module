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
                template: 'Piimega_MaksuturvaBase/payment/maksuturva-base-form',
            },

            getCode: function() {
                return 'maksuturva_base_payment';
            },

            getData: function () {
                var extension_attributes = {maksuturva_preselected_payment_method : ''};
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
                var form = 'form[data-role=maksuturva_base_payment-form]';
                return $(form).validation() && $(form).validation('isValid');
            },

            getPreSelectedBank: function(){
                var html = "";
                if(window.checkoutConfig.payment.maksuturva_base_payment.html != "undefined"){
                    html = window.checkoutConfig.payment.maksuturva_base_payment.html;
                }
                return html;
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.maksuturva_base_payment.mailingAddress;
            },
        });
    }
);
