define(
    [
        'Magento_Checkout/js/model/new-customer-address'
    ],
    function(
        NewCustomerAddress
    ) {
        'use strict';

        return function (addressData) {
            if (addressData.region_id) {
                // Remember the region id
                var region_id = addressData.region_id;
                addressData.region = {};
                addressData.region['region_id'] = region_id;
            }
            var masterpassAddress = new NewCustomerAddress(addressData);
            masterpassAddress.isEditable = function () {
                return false;
            }
            return masterpassAddress;
        }
    }
);