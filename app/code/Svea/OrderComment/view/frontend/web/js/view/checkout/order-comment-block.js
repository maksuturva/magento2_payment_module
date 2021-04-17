define(
    [
        'jquery',
        'uiComponent',
        'knockout'
    ],
    function ($, Component, ko) {
        'use strict';

        ko.extenders.maxOrderCommentLength = function (target, maxLength) {
            var timer;
            var result = ko.computed({
                read: target,
                write: function (val) {
                    if (maxLength > 0) {
                        clearTimeout(timer);
                        if (val.length > maxLength) {
                            var limitedVal = val.substring(0, maxLength);
                            if (target() === limitedVal) {
                                target.notifySubscribers();
                            } else {
                                target(limitedVal);
                            }
                            result.css("_error");
                            timer = setTimeout(function () { result.css(""); }, 800);
                        } else {
                            target(val);
                            result.css("");
                        }
                    } else {
                        target(val);
                    }
                }
            }).extend({ notify: 'always' });
            result.css = ko.observable();
            result(target());
            return result;
        };

        return Component.extend({
            defaults: {
                template: 'Svea_OrderComment/checkout/order-comment-block',
                config: null
            },
            initialize: function() {
                this._super();
                var self = this;
                this.setConfig();
                this.comment = ko.observable('value' in this.config ? this.config.value : '').extend({maxOrderCommentLength: this.getMaxLength()});
                this.remainingCharacters = ko.computed(function(){
                    return self.getMaxLength() - (self.comment() !== null ? self.comment().length : 0);
                });

            },
            setConfig: function () {
                this.config = 'sales' in window.checkoutConfig && 'ordercomments' in window.checkoutConfig.sales
                    ? window.checkoutConfig.sales.ordercomments
                    : {};
            },
            hasMaxLength: function() {
                return this.getMaxLength() ? this.getMaxLength() > 0 : false;
            },
            getCommentHelp: function () {
                return 'comment_help' in this.config ? this.config.comment_help : false;
            },
            getMaxLength: function () {
                return 'max_length' in this.config ? this.config.max_length : false;
            },
            getInitialCollapseState: function() {
                return 'comment_initial_collapse_state' in this.config
                    ? this.config.comment_initial_collapse_state
                    : false;
            },
            isEnabled: function() {
                return 'enabled' in this.config ? this.config.enabled : false;
            },
            isInitialStateOpened: function() {
                return this.getInitialCollapseState() === 1
            }
        });
    }
);
