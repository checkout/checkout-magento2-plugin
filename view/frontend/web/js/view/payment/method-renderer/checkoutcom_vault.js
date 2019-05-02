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
            // Start the loader
            FullScreenLoader.startLoader();

            // Get self
            var self = this;

            // Place the order
            if (AdditionalValidators.validate()) {
                Utilities.placeOrder({
                    methodId: METHOD_ID,
                    cardToken: self.cardToken
                });
            }
            else  {
                FullScreenLoader.stopLoader();
            }
        }
    });

});
