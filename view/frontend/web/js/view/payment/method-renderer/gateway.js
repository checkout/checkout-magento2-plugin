define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CheckoutCom_Magento2/payment/gateway'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'checkoutcom_magento2';
            },

            isActive: function() {
                return true;
            }
        });
    }
);