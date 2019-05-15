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
    'Magento_Checkout/js/model/payment/additional-validators',
    'CheckoutCom_Magento2/js/view/payment/utilities'
],
function ($, VaultComponent, AdditionalValidators, Utilities) {

    'use strict';

    const METHOD_ID = 'checkoutcom_vault';

    return VaultComponent.extend({
        defaults: {
            template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.phtml',
            formId: METHOD_ID + '_frm'
        },

        /**
         * @returns {exports}
         */
        initialize: function () {
            this._super();
            this.buildConfig();

            return this;
        },

        /**
         * @returns {void}
         */
        buildConfig: function () {
            this.cardData = window.checkoutConfig.payment.vault[this.getId()].config;
            this.iconData = window.checkoutConfig.payment.ccform.icons[this.cardData.details.type];
        },    

        /**
         * Get the card icon.
         *
         * @returns {String}
         */
        getIcon: function () {
            return this.iconData;
        },

        /**
         * Get last 4 digits of card.
         *
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.cardData.details.maskedCC;
        },

        /**
         * Get expiration date.
         *
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.cardData.details.expirationDate;
        },

        /**
         * Get card type.
         *
         * @returns {String}
         */
        getCardType: function () {
            return this.cardData.details.type;
        },

        /**
         * Get stored card token (encrypted card_id).
         *
         * @returns {String}
         */
        getPublicHash: function () {
            return this.cardData.publicHash;
        },

        /**
         * @returns {void}
         */
        placeOrder: function () {
            var self = this;
            if (AdditionalValidators.validate()) {
                // Place the order
                Utilities.placeOrder({
                    methodId: METHOD_ID,
                    publicHash: self.getPublicHash()
                });
            }
        }
    });

});
