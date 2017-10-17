/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/method-renderer/cc-form',
        'Magento_Vault/js/view/payment/vault-enabler',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/checkout-data'
    ],
    function ($, Component, VaultEnabler, CheckoutCom, quote, url, additionalValidators, checkoutData) {
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
               //return CheckoutCom.getPaymentConfig()['quote_value'];
               return (quote.getTotals()().grand_total*100).toFixed(2);
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
             * @returns {void}
             */
            saveSessionData: function() {
                // Prepare the session data
                var sessionData = {
                    saveShopperCard: $('#checkout_com_enable_vault').is(":checked"),
                    customerEmail: checkoutData.getValidatedEmailValue()
                };

                // Send the session data to be saved
                $.ajax({
                    url : url.build('checkout_com/shopper/sessionData'),
                    type: "POST",
                    data : sessionData,
                    success: function(data, textStatus, xhr) { },
                    error: function (xhr, textStatus, error) { } // todo - improve error handling
                });
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                // Get self
                var self = this;

                // Validate before submission
                if (additionalValidators.validate()) {
                    // Set the save card option in session
                    self.saveSessionData();

                    // Submit the form
                    $('#checkout_com-hosted-form').submit();
                }
            }

        });
    }

);
