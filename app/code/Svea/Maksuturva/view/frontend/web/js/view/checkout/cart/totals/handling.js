/**
 *  Copyright © Vaimo Group. All rights reserved.
 *  See LICENSE_VAIMO.txt for license details.
 */

define(
    [
        'Svea_Maksuturva/js/view/checkout/summary/handling'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            /**
             * @override
             */
            isDisplayed: function () {
                return this.getPureValue() > 0;
            }
        });
    }
);