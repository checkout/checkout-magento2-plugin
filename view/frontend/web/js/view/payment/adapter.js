define([
    'jquery',
    'Magento_Ui/js/model/messageList'
], function ($, globalMessageList) {
    'use strict';

    return {

        /**
         * Get payment name.
         * @returns {String}
         */
        getCode: function () {
            return 'checkout_com';
        },

        /**
         * Get payment configuration array.
         * @returns {Array}
         */
        getPaymentConfig: function() {
            return window.checkoutConfig.payment[this.getCode()];
        },

        /**
         *
         * @returns {*|{then, catch, finally}}
         */
        getClient: function() {
            var
                self = this,
                url = this.getPaymentConfig()['sdk_url'],
                deferred = $.Deferred();

            window.CKOConfig = {
                debugMode: self.getPaymentConfig()['debug_mode'],
                publicKey: self.getPaymentConfig()['public_key'],
                ready: function () {
                    deferred.resolve();
                },
                apiError: function () {
                    deferred.reject();
                }
            };

            require.undef(url);
            delete window.CheckoutKit;

            require([url], function() {});

            return deferred.promise();
        },

        /**
         * Show error message
         *
         * @param {String} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage
            });
        }

    };

});
