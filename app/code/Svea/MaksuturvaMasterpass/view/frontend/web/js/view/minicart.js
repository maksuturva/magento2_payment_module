define(
    [
        'jquery',
        'Magento_Checkout/js/view/minicart'
    ],
    function ($,Component) {
        'use strict';
        var isMaterpassEnabled = window.checkout.isMasterpassEnabled;
        var masterpassImageUrl = window.checkout.masterpassImageUrl;
        var newMasterpassUrl = window.checkout.newMasterpassUrl;
        return Component.extend({
            newMasterpassUrl: newMasterpassUrl,
            masterpassImageUrl: masterpassImageUrl,
            isMaterpassEnabled: isMaterpassEnabled,
            masterpassInitialize: function (element) {
                var self = this;
                $(element).on('click', function () {
                    window.location.href = self.newMasterpassUrl
                });
            }
        });
    }
);
