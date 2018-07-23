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
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Checkout/js/action/redirect-on-success'        
    ],
    function($, Component, Adapter, PlaceOrderAction, Url, FullScreenLoader, AdditionalValidators, VaultEnabler, RedirectOnSuccessAction) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: Adapter.getName() + '/payment/embedded',
                code: Adapter.getCode(),
                redirectAfterPlaceOrder: true
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
             * @returns {string}
             */
            getCode: function() {
                return this.code;
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function () {
                return this.vault.isVaultEnabled();
            },

            /**
             * @returns {{method: (*|string|String), additional_data: {card_token_id: *}}}
             */
            getData: function() {
                return Adapter.getData();
            },

            /**
             * @returns {string}
             */
            getQuoteValue: function() {
                return Adapter.getQuoteValue();
            },

            /**
             * @returns {void}
             */
            saveSessionData: function(dataToSave) {
                Adapter.saveSessionData(dataToSave);
            },  

            /**
             * @returns {string}
             */
            php: function(functionName) {
                return Adapter.getPaymentConfig()[functionName];
            },

            getPlaceOrderDeferredObject: function() {
                return $.when(
                    PlaceOrderAction(this.getData(), this.messageContainer)
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
                    if (Frames.isCardValid()) {
                        // Set the save card option in session
                        self.saveSessionData();

                        // Submit frames form
                        Frames.submitCard();
                    }
                }
            },

            /**
             * @override
             */
            placeOrder: function() {
                var self = this;
                $.migrateMute = true;
                this.updateButtonState(false);
                this.getPlaceOrderDeferredObject()
                .fail(
                    function() {
                        self.updateButtonState(true);
                        $('html, body').animate({ scrollTop: 0 }, 'fast');
                        self.reloadEmbeddedForm();
                    }
                ).done(
                    function() {
                        self.afterPlaceOrder();

                        if (self.redirectAfterPlaceOrder) {
                            RedirectOnSuccessAction.execute();
                        }
                    }
                );
            },
                        
            /**
             * @returns {void}
             */
            getEmbeddedForm: function() {
                // Get self
                var self = this;

                // Prepare parameters
                var ckoTheme = CheckoutCom.getPaymentConfig()['getEmbeddedTheme'];
                var redirectUrl = self.getRedirectUrl();
                var threeds_enabled = CheckoutCom.getPaymentConfig()['three_d_secure']['enabled'];
                var paymentForm = document.getElementById('embeddedForm');

                // Freeze the place order button on initialisation
                $('#ckoPlaceOrder').attr("disabled",true);

                // Initialise the embedded form
                Frames.init({
                    publicKey: self.getPublicKey(),
                    containerSelector: '#cko-form-holder',
                    theme: ckoTheme,
                    frameActivated: function () {
                        $('#ckoPlaceOrder').attr("disabled", true);
                    },
                    cardValidationChanged: function() {
                        self.updateButtonState(!(Frames.isCardValid() && quote.billingAddress() != null));
                    },
                    cardTokenised: function(event) {
                        // Set the card token
                        self.setCardTokenId(event.data.cardToken);

                        // Add the card token to the form
                        Frames.addCardToken(paymentForm, event.data.cardToken);

                        // Place order
                        if (threeds_enabled) {
                            window.location.replace(redirectUrl + '?cko-card-token=' + event.data.cardToken + '&cko-context-id=' + self.getEmailAddress());
                        } else {
                            self.placeOrder();
                        }
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