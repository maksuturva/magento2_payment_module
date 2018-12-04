var config = {
    config: {
        mixins: {
            'Magento_Customer/js/model/customer-addresses': {
                'Piimega_MaksuturvaMasterpass/js/customer/model/customer-addresses-mixin': true
            },
            'Magento_Checkout/js/action/select-billing-address': {
                'Piimega_MaksuturvaMasterpass/js/checkout/action/select-billing-address-mixin': true
            }
        }
    }
};