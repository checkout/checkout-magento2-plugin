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
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, VaultEnabler, CheckoutCom, quote, url, additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/embedded',
                code: 'checkout_com',
                card_token_id: null
            },

            /**
             * @returns {exports}
             */
            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {string}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            },

            /**
             * @returns {string}
             */
            getCode: function() {
                return CheckoutCom.getCode();
            },

            /**
             * @param {string} card_token_id
             */
            setCardTokenId: function(card_token_id) {
                this.card_token_id = card_token_id;
            },

            /**
             * @returns {bool}
             */
            isActive: function() {
                return CheckoutCom.getPaymentConfig()['isActive'];
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
            getQuoteValue: function() {
                return CheckoutCom.getPaymentConfig()['quote_value'];
            },

            /**
             * @param {string} card_token_id
             */
            setCardTokenId: function(card_token_id) {
                this.card_token_id = card_token_id;
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
            getEmailAddress: function() {
                return window.checkoutConfig.customerData.email || quote.guestEmail;
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                if (additionalValidators.validate()) {
                    if (Checkout.isCardFormValid()) {
                        Checkout.submitCardForm();
                    }
                }
            },

            /**
             * @returns {void}
             */
            getEmbeddedForm: function() {
                // Get self
                var self = this;

                // Prepare parameters
                var ckoTheme = CheckoutCom.getPaymentConfig()['embedded_theme'];
                var embedded_css = CheckoutCom.getPaymentConfig()['embedded_css'];
                var ckoThemeOverride = ((embedded_css) && embedded_css !== '') ? embedded_css : undefined;

                // Initialise the embedded form
                Checkout.init({
                    publicKey: self.getPublicKey(),
                    customerEmail: self.getEmailAddress(),
                    value: self.getQuoteValue(),
                    currency: self.getQuoteCurrency(),
                    redirectUrl: self.getRedirectUrl(),
                    cancelUrl: self.getCancelUrl(),
                    appMode: 'embedded',
                    appContainerSelector: '#embeddedForm',
                    theme: ckoTheme,
                    themeOverride: ckoThemeOverride,
                    cardTokenised: function(event) {
                            self.setCardTokenId(event.data.cardToken);
                            self.placeOrder();
                    }
                });
            },
        });
    }
);
