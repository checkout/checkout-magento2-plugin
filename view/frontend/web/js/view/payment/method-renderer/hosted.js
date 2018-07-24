/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/action/place-order',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function($, Component, Adapter, PlaceOrderAction, Url, FullScreenLoader, AdditionalValidators, VaultEnabler) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: Adapter.getName() + '/payment/hosted',
                code: Adapter.getCode(),
                targetForm: '#cko-hosted-form'
            },

            /**
             * @returns {exports}
             */
            initialize: function() {
                this._super();
                this.initObservable();
                this.vault = new VaultEnabler();
                this.vault.setPaymentCode(Adapter.getVaultCode());
            },

            initObservable: function() {
                this._super().observe([]);

                return this;
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function () {
                return this.vault.isVaultEnabled();
            },

            /**
             * Executes a js function from the adapter.
             */
            js: function(functionName, params) {
                params = params || [];
                return (params.length > 0) ? Adapter[functionName].apply(params) : Adapter[functionName]();
            },

            /**
             * @returns {string}
             */
            php: function(functionName) {
                return Adapter.getPaymentConfig()[functionName];
            },

            getPaymentToken: function (targetElementId) {
                $.ajax({
                    url: Url.build('checkout_com/payment/paymentToken'),
                    type: "GET",
                    success: function(data, textStatus, xhr) {
                        $('#' + targetElementId).val(data);
                    },
                    error: function(xhr, textStatus, error) {
                        console.log(error);
                    } // todo - improve error handling
                });               
            },      

            /**
             * @returns {string}
             */
            submitForm: function(frm) {
                // Submit the form
                $(frm).submit();
            },

            /**
             * @returns {void}
             */
            saveSessionData: function(dataToSave) {
                // Get self
                var self = this;

                // Send the session data to be saved
                $.ajax({
                    url: Url.build('checkout_com/shopper/sessionData'),
                    type: "POST",
                    data: dataToSave,
                    success: function(data, textStatus, xhr) {
                        console.log(data);
                    },
                    error: function(xhr, textStatus, error) {} // todo - improve error handling
                });
            },

            getPlaceOrderDeferredObject: function() {
                return $.when(
                    PlaceOrderAction(this.js('getData'), this.messageContainer)
                );
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                // Get self
                var self = this;

                // Validate before submission
                if (AdditionalValidators.validate()) {
                    // Payment action
                    if (this.php('getOrderCreation') == 'before_auth') {
                        // Start the loader
                        FullScreenLoader.startLoader();

                        // Prepare the vars
                        var ajaxRequest;
                        var orderData = {
                            "agreement": [true]
                        };

                        // Avoid duplicate requests
                        if (ajaxRequest) {
                            ajaxRequest.abort();
                        }

                        // Send the request
                        ajaxRequest = $.ajax({
                            url: Url.build('checkout_com/payment/placeOrderAjax'),
                            type: "post",
                            data: orderData
                        });

                        // Callback handler on success
                        ajaxRequest.done(function(response, textStatus, jqXHR) {
                            // Save order track id response object in session
                            self.saveSessionData({
                                customerEmail: self.js('getEmailAddress'),
                                orderTrackId: response.trackId
                            });

                            // Proceed with submission
                            FullScreenLoader.stopLoader();
                            self.submitForm(self.targetForm);
                        });

                        // Callback handler on failure
                        ajaxRequest.fail(function(jqXHR, textStatus, errorThrown) {
                            // Todo - improve error handling
                        });

                        // Callback handler always
                        ajaxRequest.always(function() {
                            // Stop the loader
                            FullScreenLoader.stopLoader();
                        });
                    } else if (this.php('getOrderCreation') == 'after_auth') {                        
                        // Save the session data
                        self.saveSessionData({
                            customerEmail: self.js('getEmailAddress')
                        });

                        // Proceed with submission
                        self.submitForm(self.targetForm);
                    }
                }
            }
        });
    }
);