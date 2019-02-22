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
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Checkout/js/action/redirect-on-success',
        'framesjs'
    ],
    function($, Component, Adapter, PlaceOrderAction, Quote, Url, FullScreenLoader, AdditionalValidators, VaultEnabler, RedirectOnSuccessAction) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: Adapter.getName() + '/payment/embedded',
                code: Adapter.getCode()
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
                    if (Frames.isCardValid()) {
                        // Submit frames form
                        Frames.submitCard();
                    }
                    else  {
                        FullScreenLoader.stopLoader();
                    }
                }
                else {
                    FullScreenLoader.stopLoader();
                }
            },

            getPlaceOrderDeferredObject: function() {
                return $.when(
                    PlaceOrderAction(this.getData(), this.messageContainer)
                );
            },

            /**
             * @returns {void}
             */
            getEmbeddedForm: function() {
                // Get self
                var self = this;

                // Prepare parameters
                var ckoTheme = this.php('getEmbeddedTheme');
                var redirectUrl = this.php('getPlaceOrderRedirectUrl');
                var threeds_enabled = this.php('isVerify3DSecure');
                var paymentForm = document.getElementById('embeddedForm');

                // Freeze the place order button on initialization
                $('#ckoPlaceOrder').attr("disabled",true);

                // Initialize the embedded form
                Frames.init({
                    publicKey: this.php('getPublicKey'),
                    containerSelector: '#cko-form-holder',
                    theme: ckoTheme,
                    debugMode: Adapter.isDebugOn(),
                    frameActivated: function () {
                        $('#ckoPlaceOrder').attr("disabled", true);
                    },
                    cardSubmitted: function() {
                        $('#ckoPlaceOrder').attr("disabled", true);
                    },
                    cardValidationChanged: function(event) {
                        self.updateButtonState(!(Frames.isCardValid() && Quote.billingAddress() != null));
                    },
                    cardTokenised: function(event) {     
                        // Set the card BIN
                        Adapter.setCardBin(event.data.card.bin);

                        // Add the card token to the form
                        Frames.addCardToken(paymentForm, event.data.cardToken);

                        // Submit the payment form
                        paymentForm.submit();
                    },
                });  
            },

            /**
             * @returns {void}
             */
            updateButtonState: function(status) {
                $('#ckoPlaceOrder').attr("disabled", status);
            },

            /**
             * @returns {void}
             */
            reloadEmbeddedForm: function() {
                // Get self
                var self = this;

                // Reload the iframe
                $('#cko-form-holder form iframe').remove();
                self.getEmbeddedForm();
            }
        });
    }

);