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
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Vault/js/view/payment/vault-enabler',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/place-order',
        'CheckoutCom_Magento2/js/view/payment/response-strategy',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, VaultEnabler, CheckoutCom, quote, placeOrderAction, responseStrategy, checkoutData, additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/form',
                code: 'checkout_com',
                card_token_id: null,
                creditCardHolder: ''
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardHolder'
                    ]);

                return this;
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
             * @returns {{method: (*|string|String), additional_data: {card_token_id: *}}}
             */
            getData: function() {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'card_token_id': this.card_token_id
                    }
                };

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            /**
             * @returns {string}
             */
            getEmailAddress: function() {
                return window.checkoutConfig.customerData.email || quote.guestEmail || checkoutData.getValidatedEmailValue();
            },

            /**
             * @returns {object}
             */
            getCardTokenData: function() {
                var self = this;

                var data = {
                    expiryMonth: self.creditCardExpMonth(),
                    expiryYear: self.creditCardExpYear(),
                    number: self.creditCardNumber(),
                    name: self.creditCardHolder(),
                    'email-address': self.getEmailAddress()
                };

                if(this.hasVerification()) {
                    data.cvv = self.creditCardVerificationNumber();
                }

                return data;
            },
            
            getCcAvailableTypes: function() {
                return window.checkoutConfig.payment[this.getCode()].availableCardTypes;
            },
            
            beforePlaceOrder: function() {
                if (additionalValidators.validate()) {               
                    var self = this;
                    CheckoutCom.getClient().done(function() {
                        CheckoutKit.createCardToken(self.getCardTokenData(), {includeBinData: false}, function(response) {
                            if ('error' === response.type) {
                                CheckoutCom.showError('It looks like you have entered incorrect bank card data.');
                            }
                            else {
                                self.setCardTokenId(response.id);
                                self.placeOrder();
                            }
                        });
                    });
                }
            },

            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                ).then(responseStrategy);
            }

        });
    }

);
