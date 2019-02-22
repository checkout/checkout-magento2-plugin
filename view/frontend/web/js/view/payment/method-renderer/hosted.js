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
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.php('getVaultCode'));
                Adapter.setCustomerData();
            },

            initObservable: function() {
                this._super().observe([]);

                return this;
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function() {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {Object}
             */
            getData: function () {
                var data = this._super();

                this.vaultEnabler.visitAdditionalData(data);

                return data;
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
                    url: Url.build(this.code + '/payment/paymenttoken'),
                    type: "GET",
                    success: function(data, textStatus, xhr) {
                        $('#' + targetElementId).val(data);
                    },
                    error: function(xhr, textStatus, error) {
                        Adapter.watchdog(error);
                    }
                });               
            },      

            /**
             * @returns {string}
             */
            submitForm: function(frm) {
                // Submit the form
                $(frm).submit();
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
                // Start the loader
                FullScreenLoader.startLoader();

                // Get self
                var self = this;

                // Validate before submission
                if (AdditionalValidators.validate()) {                      
                    // Proceed with submission
                    self.submitForm(self.targetForm);
                }
                else  {
                    FullScreenLoader.stopLoader();
                }
            }
        });
    }
);