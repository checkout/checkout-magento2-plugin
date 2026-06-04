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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

/**
 * Shared Flow session loader (singleton).
 *
 * The checkout page renders several Checkout.com Flow payment methods (Card + APMs, Google Pay,
 * Apple Pay) as independent Magento renderers. Without coordination each would call
 * `checkout_com/flow/prepare` and create its own CheckoutWebComponents instance — i.e. multiple
 * payment sessions per page. This module guarantees a SINGLE prepare call + a SINGLE
 * CheckoutWebComponents instance, shared by every method; each method then `create()`s its own
 * components from that shared instance.
 */
define(
    [
        'mage/url',
        'flowjs',
        'CheckoutCom_Magento2/js/common/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Url, CheckoutWebComponents, Utilities, FullScreenLoader) {
        'use strict';

        let loadPromise = null;
        let sharedData = null;
        const reloadSubscribers = [];

        /**
         * Build the prepare URL, signalling native Apple Pay availability (as the stock flow did).
         * @returns {string}
         */
        function buildPrepareUrl() {
            const baseUrl = Url.build('checkout_com/flow/prepare'),
                applePay = globalThis.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_apple_pay,
                merchantId = applePay?.merchant_id,
                applePaySession = globalThis.ApplePaySession,
                separatorUrl = baseUrl.includes('?') ? '&' : '?',
                isFlowApplePayOnAllBrowser = applePay?.flow_enabled_on_all_browsers === '1';
            let isNative = '0';

            if (isFlowApplePayOnAllBrowser) {
                isNative = '1';
            } else if (applePaySession && applePay && merchantId) {
                try {
                    if (applePaySession.canMakePayments(merchantId)) {
                        isNative = '1';
                    }
                } catch (e) {
                    Utilities.log(e);
                }
            }

            return baseUrl + separatorUrl + 'flow_apple_pay_is_native=' + isNative;
        }

        /**
         * Perform the single prepare + CheckoutWebComponents init.
         * @returns {Promise<{checkout: Object, data: Object}>}
         */
        function init() {
            loadPromise = (async function () {
                const response = await fetch(buildPrepareUrl(), { method: 'GET' });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error('Flow prepare failed');
                }

                sharedData = data;

                let appearance = data.appearance;

                if (appearance !== '') {
                    try {
                        appearance = JSON.parse(appearance);
                    } catch (e) {
                        Utilities.log(e);
                        appearance = '';
                    }
                }

                const checkout = await CheckoutWebComponents({
                    paymentSession: data.paymentSession,
                    publicKey: data.publicKey,
                    environment: data.environment,
                    appearance: appearance,
                    componentOptions: {
                        flow: { showPayButton: false },
                        card: {
                            displayCardholderName: Number(
                                globalThis.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_card_payment?.display_cardholder_name
                            ) === 0 ? 'hidden' : 'top'
                        }
                    },
                    onError: (component, error) => {
                        const paymentId = error.details?.paymentSessionId;

                        Utilities.log('Flow error with payment method ' + component?.type, error);
                        FullScreenLoader.stopLoader();

                        if (paymentId) {
                            Utilities.redirectFailedPayment(paymentId, null);
                        }
                    }
                });

                return { checkout: checkout, data: data };
            })();

            return loadPromise;
        }

        return {
            /**
             * Get the shared {checkout, data}. The first caller triggers the single prepare +
             * CheckoutWebComponents init; subsequent callers reuse the same promise.
             * @returns {Promise<{checkout: Object, data: Object}>}
             */
            load: function () {
                if (!loadPromise) {
                    init();
                }

                return loadPromise;
            },

            /**
             * Register a callback re-run whenever the session is reloaded (country / totals change).
             * @param {Function} fn - fn(checkout, data)
             */
            onReload: function (fn) {
                reloadSubscribers.push(fn);
            },

            /**
             * Discard the current session and create a fresh one, then notify all subscribers so
             * every method re-builds its components against the new session.
             * @returns {Promise<{checkout: Object, data: Object}>}
             */
            reload: function () {
                loadPromise = null;
                sharedData = null;

                const promise = this.load();

                promise.then(function (result) {
                    reloadSubscribers.forEach(function (fn) {
                        try {
                            fn(result.checkout, result.data);
                        } catch (e) {
                            Utilities.log(e);
                        }
                    });
                });

                return promise;
            },

            /**
             * @returns {Object|null} the prepare response data (paymentSession, publicKey, ...)
             */
            getData: function () {
                return sharedData;
            },

            /**
             * @returns {string|null} the shared payment session id
             */
            getSessionId: function () {
                return sharedData?.paymentSession?.id ?? null;
            }
        };
    }
);
