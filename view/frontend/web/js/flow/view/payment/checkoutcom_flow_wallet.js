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

define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'CheckoutCom_Magento2/js/flow/model/flow-loader',
        'CheckoutCom_Magento2/js/common/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, ko, Component, Url, FlowLoader, Utilities, AdditionalValidators, FullScreenLoader) {
        'use strict';

        // Maps each standalone wallet payment method to its Flow SDK component type and
        // to the config key used for 3DS lookup.
        const WALLET_MAP = {
            'checkoutcom_flow_google_pay': { sdkType: 'googlepay', configKey: 'checkoutcom_google_pay' },
            'checkoutcom_flow_apple_pay':  { sdkType: 'applepay',  configKey: 'checkoutcom_apple_pay' }
        };

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/flow/payment/checkoutcom_flow_wallet',
                    // The whole method row is hidden until the wallet is confirmed available
                    // (e.g. Apple Pay only on Safari/Apple devices).
                    walletAvailable: ko.observable(false),
                },
                reference: null,
                paymentSessionId: null,
                walletComponent: null,
                isLoading: false,

                /**
                 * @return {string} the Magento payment method code (e.g. checkoutcom_flow_google_pay)
                 */
                getCode: function () {
                    return this.item.method;
                },

                /**
                 * @return {string} the Flow SDK component type ('googlepay' | 'applepay')
                 */
                getWalletType: function () {
                    const map = WALLET_MAP[this.getCode()];

                    return map ? map.sdkType : null;
                },

                initEvents: function () {
                    if (this.getWalletType() && !this.isLoading) {
                        this.isLoading = true;
                        this.loadFlow();
                    }
                },

                /**
                 * Build this wallet's component from the SHARED Flow session (single prepare +
                 * single CheckoutWebComponents for the whole page). Re-builds on session reload.
                 * @returns {Promise<void>}
                 */
                loadFlow: function () {
                    const self = this;

                    if (!this._flowReloadBound) {
                        this._flowReloadBound = true;
                        FlowLoader.onReload(function (checkout, data) {
                            self.buildComponent(checkout, data);
                        });
                    }

                    return FlowLoader.load()
                        .then(function (result) {
                            return self.buildComponent(result.checkout, result.data);
                        })
                        .catch(function (e) {
                            Utilities.log(e);
                        })
                        .finally(function () {
                            self.isLoading = false;
                        });
                },

                /**
                 * Create the wallet component from the shared checkout, gate on isAvailable(), mount
                 * it and reveal the method row. If unavailable, the row stays hidden.
                 * @param {Object} checkout - shared CheckoutWebComponents instance
                 * @param {Object} data - shared prepare response
                 * @returns {Promise<void>}
                 */
                buildComponent: async function (checkout, data) {
                    const self = this;
                    const walletType = this.getWalletType();

                    if (!walletType || !checkout) {
                        return;
                    }

                    this.paymentSessionId = data && data.paymentSession ? data.paymentSession.id : null;

                    const component = checkout.create(walletType, {
                        showPayButton: true,
                        handleSubmit: async (_self, submitData) => {
                            return self.submitPaymentWithReference(_self, submitData);
                        },
                        onPaymentCompleted: async (_self, paymentResponse) => {
                            if (paymentResponse.status === 'Approved') {
                                Utilities.redirectCompletedPayment(paymentResponse.id, self.reference);
                            }
                            FullScreenLoader.stopLoader();
                        }
                    });

                    let isAvailable = false;

                    try {
                        isAvailable = typeof component.isAvailable === 'function'
                            ? await component.isAvailable()
                            : true;
                    } catch (e) {
                        isAvailable = false;
                        Utilities.log(e);
                    }

                    if (isAvailable) {
                        // Unmount a previous instance (e.g. after a session reload) before remounting.
                        if (this.walletComponent && typeof this.walletComponent.unmount === 'function') {
                            try {
                                this.walletComponent.unmount();
                            } catch (e) {
                                Utilities.log(e);
                            }
                        }

                        this.walletComponent = component;
                        this.walletAvailable(true);

                        const container = document.getElementById(this.getCode() + '_wallet_container');

                        if (container) {
                            container.innerHTML = '';
                            component.mount(container);
                        }
                    } else {
                        this.walletAvailable(false);
                    }
                },

                /**
                 * Place the Magento order (with THIS wallet method code) to get a reference, then
                 * submit the payment to Checkout.com with session_data + reference. Mirrors the card
                 * flow's handleSubmit so order linking + 3DS behave identically.
                 * @param {Object} walletSelf
                 * @param {Object} submitData - contains session_data
                 * @returns {Promise<Object>}
                 */
                submitPaymentWithReference: function (walletSelf, submitData) {
                    const self = this;
                    const methodId = this.getCode();
                    const payload = {
                        methodId: methodId,
                        selectedMethod: this.getWalletType()
                    };

                    if (!AdditionalValidators.validate()) {
                        FullScreenLoader.stopLoader();

                        return Promise.reject(new Error('Validation failed'));
                    }

                    FullScreenLoader.startLoader();

                    const has3DS = this.get3DSInfos();

                    return Utilities.placeOrder(payload, methodId, false, has3DS)
                        .then(function (orderResponse) {
                            if (!orderResponse || !orderResponse.success) {
                                FullScreenLoader.stopLoader();
                                if (orderResponse && orderResponse.message) {
                                    self.showMessage('error', orderResponse.message, methodId);
                                }
                                return Promise.reject(orderResponse || new Error('Place order failed'));
                            }

                            self.reference = orderResponse.reference || null;
                            Utilities.cleanCustomerShippingAddress();

                            if (!self.paymentSessionId || !submitData?.session_data || !self.reference) {
                                FullScreenLoader.stopLoader();

                                return Promise.reject(new Error('Missing session or reference'));
                            }

                            const formKey = (document.querySelector('input[name="form_key"]') || {}).value;
                            const submitUrl = Url.build('checkout_com/flow/submit')
                                + (formKey ? '?form_key=' + encodeURIComponent(formKey) : '');

                            return fetch(submitUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    session_id: self.paymentSessionId,
                                    session_data: submitData.session_data,
                                    reference: self.reference
                                })
                            });
                        })
                        .then(function (submitResponse) {
                            return submitResponse.json().then(function (responseData) {
                                if (!submitResponse.ok || responseData.error) {
                                    FullScreenLoader.stopLoader();
                                    self.showMessage('error', responseData.message || 'Payment submit failed', methodId);

                                    return Promise.reject(responseData);
                                }
                                return responseData;
                            });
                        });
                },

                /**
                 * 3DS flag for this wallet from checkoutConfig.
                 * @returns {boolean}
                 */
                get3DSInfos: function () {
                    const map = WALLET_MAP[this.getCode()];
                    const info = map && window.checkoutConfig.payment.checkoutcom_magento2[map.configKey];

                    if (!info) {
                        return false;
                    }

                    return !!(info.three_ds && info.three_ds === '1');
                },

                /**
                 * Show a message in this method's message area (falls back to global).
                 */
                showMessage: function (type, message) {
                    Utilities.showMessage(type, message, this.getCode());
                }
            }
        );
    }
);
