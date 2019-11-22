var config = {
    config: {
        mixins: {
            'Magento_Customer/js/model/customer-addresses': {
                'Svea_MaksuturvaMasterpass/js/customer/model/customer-addresses-mixin': true
            },
            'Magento_Checkout/js/action/select-billing-address': {
                'Svea_MaksuturvaMasterpass/js/checkout/action/select-billing-address-mixin': true
            }
        }
    }
};