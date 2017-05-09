/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/method-renderer/cc-form',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, CheckoutCom, quote, url, additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/hosted'
            },

            /**
             * @returns {string}
             */
            getHostedUrl: function() {
                return CheckoutCom.getPaymentConfig()['hosted_url'];
            },

            /**
             * @returns {string}
             */
            getPublicKey: function() {
                return CheckoutCom.getPaymentConfig()['public_key'];
            },

            /**
             * @returns {string}
             */
            getPaymentMode: function() {
                return CheckoutCom.getPaymentConfig()['payment_mode'];
            },

            /**
             * @returns {string}
             */
            getPaymentToken: function() {
                return CheckoutCom.getPaymentConfig()['payment_token'];
            },

            /**
             * @returns {string}
             */
            getQuoteValue: function() {
                return CheckoutCom.getPaymentConfig()['quote_value'];
            },

            /**
             * @returns {string}
             */
            getQuoteCurrency: function() {
                return CheckoutCom.getPaymentConfig()['quote_currency'];
            },

            /**
             * @returns {string}
             */
            getRedirectUrl: function() {
                return url.build('checkout_com/payment/placeOrder');
            },

            /**
             * @returns {string}
             */
            getCancelUrl: function() {
                return window.location.href;
            },

            /**
             * @returns {string}
             */
            getDesignSettings: function() {
                return CheckoutCom.getPaymentConfig()['design_settings'];
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                if (additionalValidators.validate()) {
                    $('#checkout_com-hosted-form').submit();
                }
            }

        });
    }

);
