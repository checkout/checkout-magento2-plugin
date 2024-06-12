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
<<<<<<< release/v6.2.0
    'CheckoutCom_Magento2/js/model/checkout-utilities',
=======
>>>>>>> master
    'CheckoutCom_Magento2/js/view/payment/paypal-utilities',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'mage/url',
<<<<<<< release/v6.2.0
], function ($, ko, Component, Utilities, CheckoutUtilities, PaypalUtilities, FullScreenLoader, AdditionalValidators, Quote, __, Url) {
=======
], function ($, ko, Component, Utilities, PaypalUtilities, FullScreenLoader, AdditionalValidators, Quote, __, Url) {
>>>>>>> master
    'use strict';

    window.checkoutConfig.reloadOnBillingAddress = true;
    const METHOD_ID = 'checkoutcom_paypal';
    let loadEvents = true;
    let loaded = false;

    return Component.extend({
        defaults: {
            template: 'CheckoutCom_Magento2/payment/' + METHOD_ID +
                '.html',
        },
        paypalScriptUrl: 'https://www.paypal.com/sdk/js',
        placeOrderEnable: ko.observable(false),
        buttonId: METHOD_ID + '_btn',
        chkPayPalOrderid: null,
        chkPayPalContextId: null,

        /**
         * @return {void}
         */
        initialize: function () {
            this._super();

            this.paypalCheckoutConfig = {
                paypalScriptUrl: this.paypalScriptUrl,
                clientId: this.getValue('checkout_client_id'),
                merchantId: this.getValue('merchant_id'),
                partnerAttributionId: this.getValue('checkout_partner_attribution_id'),
                ...window.paypalCheckoutConfig
            }

            const scriptPromise = PaypalUtilities.paypalScriptLoader(this.paypalCheckoutConfig);

            scriptPromise.then(() => {
                this.placeOrderEnable(true);
            }).catch((error) => {
                Utilities.log(error);
            });
<<<<<<< release/v6.2.0

            CheckoutUtilities.initSubscribers(this);
=======
>>>>>>> master
        },

        /**
         * @param {HTMLDivElement} element
         * @return {void}
         */
        renderPaypalButton: function (element) {
            paypal.Buttons({
                createOrder: async () => {
                    return await this._getPaypalOrderId();
                },
                onApprove: async (data) => {
                    this.placeOrder();
                },
            }).render(element);
        },

        /**
         * @return {string}
         */
        getCode: function () {
            return METHOD_ID;
        },

        /**
         * @param {string} field
         * @return {string}
         */
        getValue: function (field) {
            return Utilities.getValue(METHOD_ID, field);
        },

        /**
         * @return {void}
         */
        checkLastPaymentMethod: function () {
            return Utilities.checkLastPaymentMethod();
        },

        /**
         * @return {Promise}
         */
        _getPaypalOrderId: function () {
            return fetch(Url.build('checkout_com/paypal/context'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
            })
            .then(response => response.json())
            .then(response => {
                this.chkPayPalContextId = response.content.id;
                this.chkPayPalOrderid = response.content.partner_metadata.order_id;

                return this.chkPayPalOrderid;
            })
            .catch((response) => {
                Utilities.log(response);
                Utilities.showMessage('error', __('Something went wrong with paypal method. Please choose another method.'), METHOD_ID);
            });
        },

        /**
         * @return {void}
         */
        placeOrder: function () {
            FullScreenLoader.startLoader();

            if (Utilities.methodIsSelected(METHOD_ID) &&
                this.chkPayPalContextId) {
                let data = {
                    methodId: METHOD_ID,
                    contextPaymentId: this.chkPayPalContextId,
                };

                // Place the order
                if (AdditionalValidators.validate()) {
                    Utilities.placeOrder(
                        data,
                        METHOD_ID,
                        function () {
                            Utilities.log(__('Success'));
                        },
                        function () {
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
