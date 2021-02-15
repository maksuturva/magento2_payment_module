define(
    [
        'jquery',
        'uiComponent',
        'knockout'
    ],
    function ($, Component, ko) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Svea_OrderComment/checkout/terms-block',
                config: null
            },
            initialize: function() {
                this._super();
                var self = this;
                this.setConfig();
            },
            setConfig: function () {
                this.config = 'maksuturva' in window.checkoutConfig && 'terms' in window.checkoutConfig.maksuturva
                    ? window.checkoutConfig.maksuturva.terms
                    : {};
            },
            getText: function () {
                return 'text' in this.config ? this.config.text : false;
            },
            isEnabled: function() {
                return 'enabled' in this.config ? this.config.enabled : false;
            },
        });
    }
);
