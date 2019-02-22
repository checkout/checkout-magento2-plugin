/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/action/place-order',
    'CheckoutCom_Magento2/js/view/payment/adapter',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, VaultComponent, placeOrderAction, Adapter, AdditionalValidators, FullScreenLoader) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'CheckoutCom_Magento2/payment/vault'
        },

        getVaultTitle: function () {
            return this.php('getVaultTitle');
        },

        /**
         * Get last 4 digits of card.
         *
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date.
         *
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type.
         *
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * Get stored card token (encrypted card_id).
         *
         * @returns {String}
         */
        getPublicHash: function () {
            return this.publicHash;
        },

        /**
         * @returns {string}
         */
        beforePlaceOrder: function() {
            // Start the loader
            FullScreenLoader.startLoader();

            // Get self
            var self = this;

            // Place the order
            if (AdditionalValidators.validate()) {
                $('#cko-vault-form').submit();
            }
            else  {
                FullScreenLoader.stopLoader();
            }
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
        }
    });

});
