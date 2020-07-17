define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-totals',
        'mage/validation'
    ],
    function ($, ko, Component, selectPaymentMethodAction,quote, checkoutData, urlBuilder, url,customer, additionalValidators, placeOrderAction, fullScreenLoader, getTotalsAction) {
        'use strict';

        var selectedMethod = ko.observable();
        var selectedSveaMethod = checkoutData.getSelectedPaymentMethod();

        return Component.extend({
            //be implemented in sub-class
            getCode: function() {},

            getData: function () {
                return {
                    "method": this.item.method,
                    "extension_attributes": {maksuturva_preselected_payment_method : this.selectedPayment()}
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

                if(this.isPreselectRequired() && (this.selectedPayment() === undefined || this.selectedPayment() === null)){
                    return false;
                }
                return $(form).validation() && $(form).validation('isValid');
            },

            selectedPayment: selectedMethod,

            selectSubMethod : function (data, event){
                this.selectedPayment(event.target.value);
                return true;
            },

            //it is EASY TO HACK in this coding way, but it doesn't matter in case
            isPreselectRequired: function (){
                return window.checkoutConfig.payment[this.getCode()]['preselectRequired'];
            }
        });
    }
);