/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define([
    'jquery',
    'knockout',
    'Magento_Checkout/js/view/payment/default',
    'CheckoutCom_Magento2/js/view/payment/utilities',
    'CheckoutCom_Magento2/js/model/checkout-utilities',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'mage/url',
], function($, ko, Component, Utilities, CheckoutUtilities, FullScreenLoader,
            AdditionalValidators, Quote, __, Url) {
    'use strict';

    window.checkoutConfig.reloadOnBillingAddress = true;
    const METHOD_ID = 'checkoutcom_klarna';
    let loadEvents = true;
    let loaded = false;

    return Component.extend({
        defaults: {
            template: 'CheckoutCom_Magento2/payment/' + METHOD_ID +
                '.html',
        },
        placeOrderEnable: ko.observable(false),
        buttonId: METHOD_ID + '_btn',
        chkKlarnaSessionId: null,
        chkKlarnaClientToken: null,
        chkKlarnaContextId: null,

        /**
         * @return {void}
         */
        initialize: function() {
            this._super();
            CheckoutUtilities.initSubscribers(this);
            this.getKlarnaContextDatas();
        },

        /**
         * @return {string}
         */
        getCode: function() {
            return METHOD_ID;
        },

        /**
         * @param {string} field
         * @return {string}
         */
        getValue: function(field) {
            return Utilities.getValue(METHOD_ID, field);
        },

        /**
         * @return {void}
         */
        checkLastPaymentMethod: function() {
            return Utilities.checkLastPaymentMethod();
        },

        /**
         * @return {Promise}
         */
        getKlarnaContextDatas: function() {


            fetch(Url.build('checkout_com/klarna/context'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(response => response.json()).then(response => {
                this.chkKlarnaClientToken = response.content.partner_metadata.client_token;
                this.chkKlarnaSessionId = response.content.partner_metadata.session_id;
                this.chkKlarnaContextId = response.content.id;
                window.klarnaAsyncCallback = function() {
                    Klarna.Payments.init({
                        client_token: this.chkKlarnaClientToken,
                    });
                };
                Klarna.Payments.load(
                    {
                        container: '#klarna-payments-container',
                    },
                    {},
                    function(res) {
                        alert('lalala2');
                        alert(res);
                        console.log('ICICICICICI');
                        console.log(res);
                    },
                );
            }).catch((response) => {
                Utilities.log(response);
                Utilities.showMessage('error',
                    __('Something went wrong with klarna method. Please choose another method.'),
                    METHOD_ID);
            });
        },

        /**
         * @return {void}
         */
        placeOrder: function() {
            FullScreenLoader.startLoader();

            if (Utilities.methodIsSelected(METHOD_ID) &&
                this.chkKlarnaContextId) {
                let data = {
                    methodId: METHOD_ID,
                    contextPaymentId: this.chkKlarnaContextId,
                };

                // Place the order
                if (AdditionalValidators.validate()) {
                    Utilities.placeOrder(
                        data,
                        METHOD_ID,
                        function() {
                            Utilities.log(__('Success'));
                        },
                        function() {
                            Utilities.log(__('Fail'));
                        },
                    );
                    Utilities.cleanCustomerShippingAddress();
                }

                FullScreenLoader.stopLoader();
            }
        },
    });
});
