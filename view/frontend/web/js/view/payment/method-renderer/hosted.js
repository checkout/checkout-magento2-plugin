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
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, VaultEnabler, CheckoutCom, quote, url, checkoutData, fullScreenLoader, additionalValidators) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

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
               return (quote.getTotals()().grand_total*100).toFixed(2);
            },

            /**
             * @returns {string}
             */
            getQuoteCurrency: function() {
                return CheckoutCom.getPaymentConfig()['quote_currency'];
            },

            /**
             * @returns {bool}
             */
            isCardAutosave: function() {
                return CheckoutCom.getPaymentConfig()['card_autosave'];
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
            saveSessionData: function(dataToSave) {                
                // Send the session data to be saved
                $.ajax({
                    url : url.build('checkout_com/shopper/sessionData'),
                    type: "POST",
                    data : dataToSave,
                    success: function(data, textStatus, xhr) { },
                    error: function (xhr, textStatus, error) { } // todo - improve error handling
                });
            },

            /**
             * @returns {string}
             */
            proceedWithSubmission: function() {
                // Submit the form
                $('#checkout_com-hosted-form').submit();
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                // Get self
                var self = this;

                // Validate before submission
                if (additionalValidators.validate()) {
                    // Payment action
                    if (CheckoutCom.getPaymentConfig()['order_creation'] == 'before_auth') {

                        // Start the loader
                        fullScreenLoader.startLoader();

                        // Prepare the vars
                        var ajaxRequest;
                        var orderData = {
                            "cko-card-token": null, 
                            "cko-context-id": self.getEmailAddress(),
                            "agreement": [true]
                        };
                        
                        // Avoid duplicate requests
                        if (ajaxRequest) {
                            ajaxRequest.abort();
                        }

                        // Send the request
                        ajaxRequest = $.ajax({
                            url: url.build('checkout_com/payment/placeOrderAjax'),
                            type: "post",
                            data: orderData
                        });

                        // Callback handler on success
                        ajaxRequest.done(function (response, textStatus, jqXHR){

                            // Save order track id response object in session
                            self.saveSessionData({
                                saveShopperCard: $('#checkout_com_enable_vault').is(":checked"),
                                customerEmail: self.getEmailAddress(),
                                orderTrackId: response.trackId
                            });

                            // Proceed with submission
                            fullScreenLoader.stopLoader();
                            self.proceedWithSubmission();
                        });

                        // Callback handler on failure
                        ajaxRequest.fail(function (jqXHR, textStatus, errorThrown){
                            // Todo - improve error handling
                        });

                        // Callback handler always
                        ajaxRequest.always(function () {
                            // Stop the loader
                            fullScreenLoader.stopLoader();
                        });
                    }
                    else if (CheckoutCom.getPaymentConfig()['order_creation'] == 'after_auth') {
                        // Save the session data
                        self.saveSessionData({
                            saveShopperCard: $('#checkout_com_enable_vault').is(":checked"),
                            customerEmail: self.getEmailAddress()
                        });

                        // Proceed with submission
                        self.proceedWithSubmission();
                    }
                }
            }
        });
    }

);
