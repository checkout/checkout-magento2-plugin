define([
    'jquery',
    'underscore',
    'uiComponent'
], function ($, _, Component) {
    'use strict';

    alert('alipay');

    return Component.extend({

        defaults: {
            template: 'CheckoutCom_Magento2/payment/apm/alipay.phtml'
        },

        /**
         * Extends instance with default config, calls 'initialize' method of
         *     parent, calls 'initAjaxConfig'
         */
        initialize: function () {
            this._super();

            return this;
        }
    });
});
