/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'mage/url',
], function($, GlobalMessageList, Quote, CheckoutData, Url) {
    'use strict';

    return {

        /**
         * Get payment code.
         * @returns {String}
         */
        getCode: function() {
            return window.checkoutConfig.payment['modtag'];
        },

        /**
         * @returns {string}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        /**
         * @returns {{method: (*|string|String), additional_data: {card_token_id: *}}}
         */
        getData: function() {
            var data = {
                'method': this.getCode()
            };

            return data;
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
            return window.checkoutConfig.customerData.email || Quote.guestEmail || CheckoutData.getValidatedEmailValue();
        },

        /**
         * @returns {string}
         */
        getQuoteValue: function() {
            return (Quote.getTotals()().grand_total * 100).toFixed(2);
        },

        /**
         * Get payment code.
         * @returns {String}
         */
        getCodeApplePay: function() {
            return window.checkoutConfig.payment['modtagapplepay'];
        },

        /**
         * Get payment name.
         * @returns {String}
         */
        getName: function() {
            return window.checkoutConfig.payment['modname'];
        },

        /**
         * Get payment configuration array.
         * @returns {Array}
         */
        getPaymentConfig: function() {
            return window.checkoutConfig.payment[this.getCode()];
        }, 

        /**
         * Show error message
         *
         * @param {String} errorMessage
         */
        showError: function(errorMessage) {
            GlobalMessageList.addErrorMessage({
                message: errorMessage
            });
        }
    };
});