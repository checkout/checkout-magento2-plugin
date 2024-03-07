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
    'ko',
    'uiComponent',
    'CheckoutCom_Magento2/js/view/payment/paypal-utilities',
    'Magento_Customer/js/customer-data',
    'mage/url',
], function ($, ko, Component, PaypalUtilities, CustomerData, Url) {
    'use strict';

    return Component.extend({
        isVisible: ko.observable(false),
        chkPayPalOrderid: null,
        chkPayPalContextId: null,
        paypalScriptUrl: 'https://www.paypal.com/sdk/js',
        paypalExpressInitialized: false,

        /**
         * @return {void}
         */
        initialize: function () {
            this._super();
            this.cartData = CustomerData.get('cart');
            this.customer = CustomerData.get('customer');

            this._loadPaypalScript();
            this._dataListeners();
        },

        /**
         * @return {void}
         */
        _loadPaypalScript: function () {
            if (this._canUsePaypalExpress() && !this.paypalExpressInitialized) {
                const {
                    checkout_client_id,
                    merchant_id,
                    checkout_partner_attribution_id
                } = this.cartData().checkoutcom_paypal;

                this.paypalCheckoutConfig = {
                    paypalScriptUrl: this.paypalScriptUrl,
                    clientId: checkout_client_id,
                    merchantId: merchant_id,
                    partnerAttributionId: checkout_partner_attribution_id,
                    ...window.paypalCheckoutConfig
                }

                const scriptPromise = PaypalUtilities.paypalScriptLoader(this.paypalCheckoutConfig);

                scriptPromise.then(() => {
                    this._initializePaypalExpressButton();
                }).catch((error) => {
                    console.log(error);
                });
            }
        },

        /**
         * @return {void}
         */
        _dataListeners: function () {
            this.cartData.subscribe((newCartData) => {
                this._loadPaypalScript();
            });
        },

        /**
         * @return {void}
         */
        _initializePaypalExpressButton: function () {
            this.isVisible(this.cartData().summary_count > 0);

            this.cartData.subscribe((newCartData) => {
                this.isVisible(newCartData.summary_count > 0);
            });

            this.paypalExpressInitialized = true;
        },

        /**
         * @param {HTMLDivElement} $el
         * @return {void}
         */
        renderPaypalButton: async function ($el) {
            paypal.Buttons({
                createOrder: async () => {
                    return await this._getPaypalOrderId();
                },
                onApprove: async (data) => {
                    // Redirect on paypal reviex page (express checkout)
                    window.location.href = Url.build(
                        'checkout_com/paypal/review/contextId/' +
                        this.chkPayPalContextId);
                },
            }).render($el);
        },

        /**
         * @return {void}
         */
        _canUsePaypalExpress: function () {
            const checkoutPaypalConfig = this.cartData().hasOwnProperty('checkoutcom_paypal') ?
                this.cartData().checkoutcom_paypal :
                null;

            return checkoutPaypalConfig
                && (this.cartData().isGuestCheckoutAllowed || this.customer.fullname)
                && checkoutPaypalConfig['active'] === '1'
                && checkoutPaypalConfig[this.context] === '1';
        },

        /**
         * @private
         * @return {Promise}
         */
        _getPaypalOrderId: function () {
            return fetch(Url.build('checkout_com/paypal/context'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    forceAuthorizeMode: 0
                }),
            })
            .then(response => response.json())
            .then(response => {
                this.chkPayPalContextId = response.content.id;
                this.chkPayPalOrderid = response.content.partner_metadata.order_id;

                return this.chkPayPalOrderid;
            });
        }
    });
});
