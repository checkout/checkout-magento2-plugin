define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators'
], function ($, VaultComponent, placeOrderAction, additionalValidators) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'CheckoutCom_Magento2/payment/vault'
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
        getToken: function () {
            return this.publicHash;
        },

        /**
         * @returns {string}
         */
        beforePlaceOrder: function() {

            // Get self
            var self = this;

            // Place the order
            if (additionalValidators.validate()) {
                self.placeOrder();
            }
        },
    });

});
