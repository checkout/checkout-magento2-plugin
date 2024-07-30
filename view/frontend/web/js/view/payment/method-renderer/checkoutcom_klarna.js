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
        chkKlarnaApiUrl: 'https://x.klarnacdn.net/kp/lib/v1/api.js',

        /**
         * @return {void}
         */
        initialize: function() {
            this._super();

            const scriptPromise = this.klarnaScriptLoader();

            scriptPromise.then(() => {
                CheckoutUtilities.initSubscribers(this);

                // Manage billing address change
                let self = this;
                let prevAddress;
                Quote.billingAddress.subscribe(
                    function (newAddress) {
                        if (!newAddress || !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                            prevAddress = newAddress;
                            if (newAddress) {
                                self.getKlarnaContextDatas(Quote.billingAddress().countryId);
                            }
                        }
                    }
                );

                this.getKlarnaContextDatas();
            }).catch((error) => {
                Utilities.log(error);
            });
        },

        /**
         * Load klarna script witch promise
         * @returns {Promise<unknown>}
         * @constructor
         */
        klarnaScriptLoader: function() {
            return new Promise((resolve, reject) => {
                const klarnaScript = document.querySelector(
                    `script[src*="${this.chkKlarnaApiUrl}"]`);

                if (klarnaScript) {
                    resolve();
                    return;
                }

                const script = document.createElement('script');

                script.addEventListener('load', () => {
                    resolve();
                });

                script.addEventListener('error', () => {
                    reject('Something wrong happened with Klarna script load');
                });

                this.buildScript(script);
            });
        },

        /**
         * Build Klarna script
         * @param script
         */
        buildScript: function(script) {
            const scriptUrl = new URL(this.chkKlarnaApiUrl);

            script.type = 'text/javascript';
            script.src = scriptUrl;

            document.head.appendChild(script);
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
        getKlarnaContextDatas: function(countryId = '') {
            const self = this;

            fetch(Url.build('checkout_com/klarna/context'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    country: countryId
                }),
            }).then(response => response.json()).then(response => {
                // Error on context request
                if (response.content.error) {
                    self.placeOrderEnable(false);
                    return;
                }

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
                            self.placeOrderEnable(false);
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
            const self = this;

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
                const data = {
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
