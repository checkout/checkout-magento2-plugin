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
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'CheckoutCom_Magento2/js/flow/model/flow-loader',
        'CheckoutCom_Magento2/js/common/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko, Component, Url, FlowLoader, Utilities, AdditionalValidators, FullScreenLoader) {
        'use strict';

        // Maps each standalone Flow payment method (rendered with its own pay button) to its
        // Flow SDK component type and to the config key used for 3DS lookup.
        const WALLET_MAP = {
            'checkoutcom_flow_google_pay': { sdkType: 'googlepay', configKey: 'checkoutcom_google_pay' },
            'checkoutcom_flow_apple_pay':  { sdkType: 'applepay',  configKey: 'checkoutcom_apple_pay' },
            'checkoutcom_flow_paypal':     { sdkType: 'paypal',    configKey: 'checkoutcom_paypal' }
        };

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/flow/payment/checkoutcom_flow_wallet',
                    // The whole method row is hidden until the wallet is confirmed available
                    // (e.g. Apple Pay only on Safari/Apple devices).
                    walletAvailable: ko.observable(false)
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
                 * @return {string} the Flow SDK component type ('googlepay' | 'applepay' | 'paypal')
                 */
                getWalletType: function () {
                    const map = WALLET_MAP[this.getCode()];

                    return map ? map.sdkType : null;
                },

                /**
                 * Whether the standalone Apple Pay method may be offered in the current browser.
                 *
                 * Mirrors flow-loader.js buildPrepareUrl: offer Apple Pay when "Enable Apple Pay on all
                 * browsers" is ON, OR when the browser natively supports it (window.ApplePaySession +
                 * canMakePayments()). This is enforced here because the SDK's component.isAvailable()
                 * still returns true for the cross-device (QR) flow on non-native browsers even when the
                 * config is OFF — so it cannot be the sole gate.
                 *
                 * @return {boolean}
                 */
                isApplePayOfferable: function () {
                    return Utilities.isApplePayOfferable();
                },

                /**
                 * Inline <img> of the wallet's brand logo (shipped in view/frontend/web/images/flow),
                 * shown in the standalone method's title row. Empty string if unavailable.
                 *
                 * @return {string}
                 */
                getMethodLogo: function () {
                    const logos = {
                        googlepay: 'icon-googlepay.png',
                        applepay: 'icon-applepay.png',
                        paypal: 'icon-paypal.png'
                    };
                    const type = this.getWalletType();
                    const imagesPath = window.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_data?.images_path;

                    if (type && logos[type] && imagesPath) {
                        return '<img class="cko-method-logo" alt="" src="' + imagesPath + '/' + logos[type] + '" />';
                    }

                    return '';
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
                    if (!this._flowReloadBound) {
                        this._flowReloadBound = true;
                        FlowLoader.onReload((checkout, data) => this.buildComponent(checkout, data));
                    }

                    return FlowLoader.load()
                        .then((result) => this.buildComponent(result.checkout, result.data))
                        .catch((e) => Utilities.log(e))
                        .finally(() => {
                            this.isLoading = false;
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
                    const walletType = this.getWalletType();

                    if (!walletType || !checkout) {
                        return;
                    }

                    if (walletType === 'applepay' && !this.isApplePayOfferable()) {
                        this.walletAvailable(false);
                        return;
                    }

                    this.paymentSessionId = data?.paymentSession?.id ?? null;

                    const component = checkout.create(walletType, {
                        showPayButton: true,
                        handleSubmit: (_self, submitData) => this.submitPaymentWithReference(_self, submitData),
                        onPaymentCompleted: (_self, paymentResponse) => {
                            if (paymentResponse.status === 'Approved') {
                                Utilities.redirectCompletedPayment(paymentResponse.id, this.reference);
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

                    if (!isAvailable) {
                        this.walletAvailable(false);
                        return;
                    }

                    // Unmount a previous instance (e.g. after a session reload) before remounting.
                    if (typeof this.walletComponent?.unmount === 'function') {
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
                },

                /**
                 * Place the Magento order (with THIS wallet method code) to get a reference, then
                 * submit the payment to Checkout.com with session_data + reference. Mirrors the card
                 * flow's handleSubmit so order linking + 3DS behave identically.
                 * @param {Object} walletSelf
                 * @param {Object} submitData - contains session_data
                 * @returns {Promise<Object>}
                 */
                submitPaymentWithReference: async function (walletSelf, submitData) {
                    const methodId = this.getCode();
                    const payload = {
                        methodId: methodId,
                        selectedMethod: this.getWalletType()
                    };

                    if (!AdditionalValidators.validate()) {
                        FullScreenLoader.stopLoader();

                        throw new Error('Validation failed');
                    }

                    FullScreenLoader.startLoader();

                    const orderResponse = await Utilities.placeOrder(payload, methodId, false, this.get3DSInfos());

                    if (!orderResponse?.success) {
                        FullScreenLoader.stopLoader();

                        if (orderResponse?.message) {
                            this.showMessage('error', orderResponse.message);
                        }

                        throw orderResponse ?? new Error('Place order failed');
                    }

                    this.reference = orderResponse.reference || null;
                    Utilities.cleanCustomerShippingAddress();

                    if (!this.paymentSessionId || !submitData?.session_data || !this.reference) {
                        FullScreenLoader.stopLoader();

                        throw new Error('Missing session or reference');
                    }

                    const formKey = (document.querySelector('input[name="form_key"]') || {}).value;
                    const submitUrl = Url.build('checkout_com/flow/submit')
                        + (formKey ? '?form_key=' + encodeURIComponent(formKey) : '');

                    const submitResponse = await fetch(submitUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            session_id: this.paymentSessionId,
                            session_data: submitData.session_data,
                            reference: this.reference
                        })
                    });

                    const responseData = await submitResponse.json();

                    if (!submitResponse.ok || responseData.error) {
                        FullScreenLoader.stopLoader();
                        this.showMessage('error', responseData.message || 'Payment submit failed');

                        throw responseData;
                    }

                    return responseData;
                },

                /**
                 * 3DS flag for this wallet from checkoutConfig.
                 * @returns {boolean}
                 */
                get3DSInfos: function () {
                    const map = WALLET_MAP[this.getCode()];
                    const info = map ? window.checkoutConfig?.payment?.checkoutcom_magento2?.[map.configKey] : null;

                    return !!(info && info.three_ds === '1');
                },

                /**
                 * Show a message in this method's message area.
                 */
                showMessage: function (type, message) {
                    Utilities.showMessage(type, message, this.getCode());
                }
            }
        );
    }
);
