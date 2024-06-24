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
            let self = this;
            fetch(Url.build('checkout_com/klarna/context'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(response => response.json()).then(response => {
                // Store given token
                this.chkKlarnaClientToken = response.content.partner_metadata.client_token;
                this.chkKlarnaContextId = response.content.id;

                // Init klarna api
                Klarna.Payments.init({
                    client_token: this.chkKlarnaClientToken,
                });

                // Load the klarna, check if context (res_form) allow purchase
                Klarna.Payments.load(
                    {
                        container: '#klarna-payments-container',
                    },
                    {},
                    function(res) {
                        if (res.show_form === true) {
                            // Display method
                            self.placeOrderEnable(true);
                        } else {
                            Utilities.showMessage('error',
                                __('Something went wrong with klarna method. Please choose another method.'),
                                METHOD_ID);
                        }
                    },
                );
            }).catch((response) => {
                // Here we know that klarna is disallowed for this context
                Utilities.log(response);
                Utilities.showMessage('error',
                    __('Something went wrong with klarna method. Please choose another method.'),
                    METHOD_ID);
            });
        },

        /**
         * Display the Klarna popin
         */
        authorizePayment: function() {
            let self = this;

            // Retrieve current quote datas in order to give billing informations to Klarna
            $.ajax({
                type: 'POST',
                url: Url.build('checkout_com/klarna/getCustomerDatas'),
                data: {
                    quote_id: window.checkoutConfig.quoteItemData[0].quote_id,
                    form_key: window.checkoutConfig.formKey,
                    store_id: window.checkoutConfig.quoteData.store_id,
                },
                success: function(data) {

                    // Launch klarna popin with retrieved customer datas
                    Klarna.Payments.authorize(
                        {},
                        {
                            billing_address: {
                                given_name: data.billing.firstname,
                                family_name: data.billing.lastname,
                                email: data.billing.email ||
                                    Utilities.getEmail(),
                                street_address: data.billing.street,
                                postal_code: data.billing.postcode,
                                city: data.billing.city,
                                region: data.billing.region,
                                phone: data.billing.phone,
                                country: data.billing.country_id.toLowerCase(),
                            },
                        },
                        function(res) {
                            if (res.approved === true) {
                                self.placeOrder();
                            } else {
                                Utilities.showMessage('error',
                                    __('Your payment has failed. Please try again.'),
                                    METHOD_ID);
                            }
                        },
                    );
                },
                error: self.placeOrderEnable(false),
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
