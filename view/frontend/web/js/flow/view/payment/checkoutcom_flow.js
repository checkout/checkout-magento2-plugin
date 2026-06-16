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
        'Magento_Customer/js/model/customer',
        'mage/url',
        'CheckoutCom_Magento2/js/flow/model/flow-loader',
        "CheckoutCom_Magento2/js/common/view/payment/utilities",
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, ko, Component, Customer, Url, FlowLoader, Utilities, AdditionalValidators, FullScreenLoader, StepNavigator, Quote) {
        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_flow';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/flow/payment/' + METHOD_ID + '.html',
                    buttonId: METHOD_ID + '_btn',
                    formId: METHOD_ID + '_frm',
                    formClone: null,
                    cardToken: null,
                    cardBin: null,
                    saveCard: false,
                    preferredScheme: false,
                    supportedCards: null,
                    redirectAfterPlaceOrder: false,
                    allowPlaceOrder: ko.observable(false),
                    isCoBadged: ko.observable(false),
                    tooltipVisible: ko.observable(false),
                    isLoading: false,
                    methodNameMap: {
                        'card' : 'card_payment',
                        'googlepay' : 'google_pay',
                        'applepay' : 'apple_pay'
                    },
                    currentMethod: null,
                    currentCountryCode: null,
                    // Custom radio selector state: which Flow method is selected, and card availability.
                    selectedFlowMethod: ko.observable(null),
                    cardAvailable: ko.observable(false),
                    // One entry per available APM: {type, label} — renders a radio row each.
                    availableApms: ko.observableArray([]),
                },
                reference: null,

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    window.currentGrandTotal = Quote.totals().base_grand_total;

                    this._super();

                    // When the shopper switches radio, show that method's container, lazily mount it,
                    // and update the save-card region for the selected method.
                    this.selectedFlowMethod.subscribe((method) => {
                        this.mountSelected(method);
                        this.sendSaveCardEvent(method);
                    });

                    return this;
                },

                /**
                 * @return {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @return {bool}
                 */
                isLoggedIn: function () {
                    return Customer.isLoggedIn();
                },

                initEvents: function () {
                    this.isLoading = true;
                    this.loadFlow();

                    if (Utilities.getBillingAddress().country_id) {
                        this.setCountryCode();
                    }

                    // Listen for saveCard event
                    document.querySelector('body').addEventListener(
                        "askPaymentMethod",
                        () => {
                            this.sendSaveCardEvent(this.currentMethod);
                        },
                    );

                    // Listen for Step change
                    StepNavigator.steps.subscribe((steps) => {
                        if (steps[StepNavigator.getActiveItemIndex()]['code'] === 'payment' &&
                            Utilities.getBillingAddress().country_id !== this.currentCountryCode) {
                            this.reloadFlow();
                        }
                    });

                    Quote.totals.subscribe(() => {
                        const newGrandTotal = Quote.totals().base_grand_total;
                        
                        if (Utilities.methodIsSelected(METHOD_ID) && newGrandTotal !== window.currentGrandTotal) {
                            this.reloadFlow();
                            window.currentGrandTotal = newGrandTotal;
                        }
                    }, null, 'change');
                },

                /**
                 * Set current country code
                 */
                setCountryCode: function () {
                    this.currentCountryCode = Utilities.getBillingAddress().country_id;
                },

                /**
                 * Reload Flow component if country changed
                 */
                reloadFlow: function () {
                    if (!this.isLoading) {
                        this.isLoading = true;

                        this.setCountryCode();
                        this.sendSaveCardEvent();

                        // Recreate the shared session; the onReload subscriber re-builds this method's
                        // components (and any other Flow methods on the page).
                        FlowLoader.reload().finally(() => {
                            this.isLoading = false;
                        });
                    }
                },

                /**
                 * Gets the module images path
                 */
                getImagesPath: function () {
                    return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path;
                },

                placeOrder: function () {
                    // The "Place Order" button only drives the card component.
                    // Google Pay / Apple Pay render their own native buttons and submit themselves.
                    if (Utilities.methodIsSelected(METHOD_ID) && this.flowComponents?.card) {
                        this.flowComponents.card.submit();
                    }
                },

                /**
                 * Build this method's components from the SHARED Flow session (single prepare +
                 * single CheckoutWebComponents for the whole page). Also registers a reload
                 * subscriber so the components are rebuilt if the session is recreated.
                 * @returns {Promise<void>}
                 */
                loadFlow: function () {
                    if (!this._flowReloadBound) {
                        this._flowReloadBound = true;
                        FlowLoader.onReload((checkout, data) => this.buildComponents(checkout, data));
                    }

                    return FlowLoader.load()
                        .then((result) => this.buildComponents(result.checkout, result.data))
                        .catch((e) => this.showErrorMessage(e))
                        .finally(() => {
                            this.isLoading = false;
                        });
                },

                showErrorMessage: function (message = null) {
                    Utilities.getMethodContainer(METHOD_ID).css('display','none');
                    Utilities.showGlobalMessage('error','This payment method is currently unavailable. Please select an alternative payment option to complete your purchase.');
                    Utilities.log('Error creating payment session for flow');

                    if (message) {
                        Utilities.log(message);
                    }
                },

                /**
                 * Build the Card + APM components from the shared Flow session.
                 * @param {Object} checkout - shared CheckoutWebComponents instance
                 * @param {Object} data - shared prepare response (paymentSession, ...)
                 * @returns {Promise<void>}
                 */
                buildComponents: async function (checkout, data) {
                    this.allowPlaceOrder(false);

                    // Clear anything from a previous build (e.g. after a session reload).
                    this.unmountAllComponents();

                    this.paymentSessionId = data?.paymentSession?.id ?? null;
                    this.checkout = checkout;
                    this.flowComponentInstances = {};
                    this.mountedComponents = {};
                    this.flowComponents = {};

                    // Main container = the CARD component + every available APM (iDEAL, Klarna,
                    // PayPal, SEPA, ...), each mounted as its own component. We deliberately do NOT
                    // use the bundled 'flow' component, because it always instantiates Google Pay /
                    // Apple Pay and would conflict with the standalone wallet components below.
                    await this.prepareComponent('card', 'card', 'flow-card-container', this.cardAvailable, {
                        showPayButton: false,
                        onChange: (component) => {
                            // Place Order stays disabled until the card form is valid.
                            this.allowPlaceOrder(!!component.isValid());
                        }
                    });
                    await this.probeApms();

                    // Wallets configured to display INSIDE the Flow (flow_standalone = No) are added
                    // to the same nested list. Wallets configured "outside" are handled by their own
                    // standalone Magento methods (checkoutcom_flow_google_pay / _apple_pay / _paypal).
                    await this.probeInsideWallets();

                    // Default the radio selection to Card (or the first available APM), then mount it.
                    // Subsequent switches are handled by the observable subscription.
                    const firstApm = (this.availableApms() && this.availableApms().length)
                        ? this.availableApms()[0].type
                        : null;
                    const defaultMethod = this.cardAvailable() ? 'card' : firstApm;

                    if (defaultMethod) {
                        this.selectedFlowMethod(defaultMethod);
                        this.mountSelected(defaultMethod);
                    }
                },

                /**
                 * Create a Flow payment component (NOT yet mounted) and gate it on isAvailable().
                 * Each component shares the same handleSubmit / onPaymentCompleted logic as the
                 * original bundled Flow component, so order placement + reference linking + 3DS are
                 * unchanged. The component is stored under `methodKey` for lazy mounting when its
                 * radio is selected (wallet buttons must be mounted while visible to render).
                 *
                 * @param {string} methodKey - radio/selection key ('card' = main bundle, 'googlepay', 'applepay')
                 * @param {string} createType - SDK component type to create ('flow' | 'googlepay' | 'applepay')
                 * @param {string} containerId - DOM id of the target container
                 * @param {Function} availableObservable - ko.observable toggled with availability
                 * @param {Object} extraOptions - Per-component options (e.g. showPayButton, onChange)
                 * @returns {Promise<void>}
                 */
                prepareComponent: async function (methodKey, createType, containerId, availableObservable, extraOptions) {
                    if (!this.checkout) {
                        availableObservable(false);
                        return;
                    }

                    const component = this.checkout.create(createType, this.sharedComponentOptions(extraOptions));

                    let isAvailable = true;

                    try {
                        if (typeof component.isAvailable === 'function') {
                            isAvailable = await component.isAvailable();
                        }
                    } catch (e) {
                        isAvailable = false;
                        Utilities.log(e);
                    }

                    availableObservable(!!isAvailable);

                    if (isAvailable) {
                        this.flowComponentInstances[methodKey] = { component: component, containerId: containerId };
                    }
                },

                /**
                 * Shared create() options for every component: the handleSubmit (place order ->
                 * get reference -> POST flow/submit) and onPaymentCompleted (redirect on approval)
                 * logic, identical to the original bundled Flow component.
                 *
                 * @param {Object} extraOptions - per-component extras (showPayButton, onChange, ...)
                 * @returns {Object}
                 */
                sharedComponentOptions: function (extraOptions) {
                    return {
                        handleSubmit: (_self, submitData) => this.submitPaymentWithReference(_self, submitData),
                        onPaymentCompleted: (_self, paymentResponse) => {
                            if (paymentResponse.status === "Approved") {
                                Utilities.redirectCompletedPayment(paymentResponse.id, this.reference);
                            }
                            FullScreenLoader.stopLoader();
                        },
                        ...(extraOptions || {})
                    };
                },

                /**
                 * Probe the APMs the merchant has enabled for Flow in the BACKEND
                 * (payment/checkoutcom_apm/apm_flow_enabled) and keep the ones the CKO session also
                 * reports as available. We only call CKO's isAvailable() server check for the
                 * backend-enabled list — not for every SDK type. Each kept APM is registered as its
                 * own component+container ('flow-apm-<type>') and added to availableApms so the
                 * template renders a radio row per APM. Wallets are handled separately.
                 *
                 * @returns {Promise<void>}
                 */
                probeApms: async function () {
                    this.availableApms([]);

                    if (!this.checkout) {
                        return;
                    }

                    // Wallets are NOT APMs — they have their own standalone methods / inside-Flow
                    // handling (see probeInsideWallets). Exclude them from the APM path so a wallet
                    // can never be rendered both as an APM and as a wallet, even if the admin's
                    // apm_flow_enabled list happens to contain one.
                    const walletTypes = ['googlepay', 'applepay', 'paypal'];

                    // Backend-enabled Flow APMs (comma-separated list from admin config), minus wallets.
                    const enabledApms = (window.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_apm?.apm_flow_enabled || '')
                        .split(',')
                        .map((type) => type.trim())
                        .filter(Boolean)
                        .filter((type) => !walletTypes.includes(type));

                    if (!enabledApms.length) {
                        return;
                    }

                    const probes = enabledApms.map(async (type) => {
                        try {
                            const component = this.checkout.create(type, this.sharedComponentOptions({ showPayButton: true }));
                            const available = typeof component.isAvailable === 'function'
                                ? await component.isAvailable()
                                : true;

                            return { type: type, component: component, available: available };
                        } catch (e) {
                            // Enabled in admin but the SDK could not create it (not supported).
                            Utilities.log(e);

                            return { type: type, component: null, available: false, error: e?.message || String(e) };
                        }
                    });

                    const results = await Promise.all(probes);
                    const list = [];
                    const unavailable = [];

                    results.forEach((apm) => {
                        if (apm.available && apm.component) {
                            this.flowComponentInstances[apm.type] = {
                                component: apm.component,
                                containerId: 'flow-apm-' + apm.type
                            };
                            list.push({ type: apm.type, label: this.apmLabel(apm.type) });
                        } else {
                            // Enabled in admin (apm_flow_enabled) but Checkout.com reports it unavailable.
                            unavailable.push(apm.error ? (apm.type + ' (' + apm.error + ')') : apm.type);
                        }
                    });

                    this.availableApms(list);
                    this.logUnavailableApms(unavailable);
                },

                /**
                 * Add wallets configured to display INSIDE the Flow (admin: Display as a separate
                 * payment method = No) to the main nested list, rendered like any other method (own
                 * radio + native pay button on selection). Wallets set to display outside are skipped
                 * here — they are surfaced by their own standalone Magento methods.
                 *
                 * @returns {Promise<void>}
                 */
                probeInsideWallets: async function () {
                    if (!this.checkout) {
                        return;
                    }

                    const labels = {
                        googlepay: 'Google Pay',
                        applepay: 'Apple Pay',
                        paypal: 'PayPal'
                    };

                    // SINGLE SOURCE OF TRUTH for inside-vs-outside: the BACKEND computes which wallets
                    // belong inside (enabled AND flow_standalone = No) and exposes them in
                    // checkoutConfig as checkoutcom_data.flow_inside_wallets. The standalone wallet
                    // methods (outside) are the exact complement (enabled AND flow_standalone = Yes),
                    // computed from the same scopeConfig. The frontend renders exactly this list — it
                    // does not re-derive the decision — so a wallet can never appear both inside and
                    // outside, with no dependency on config caching, exposure, or method-list timing.
                    const insideTypes = (window.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_data?.flow_inside_wallets) || [];

                    const inside = insideTypes
                        .filter((type) => labels[type])
                        .map((type) => ({ type: type, label: labels[type] }));

                    if (!inside.length) {
                        return;
                    }

                    const probes = inside.map(async (wallet) => {
                        try {
                            const component = this.checkout.create(wallet.type, this.sharedComponentOptions({ showPayButton: true }));
                            const available = typeof component.isAvailable === 'function'
                                ? await component.isAvailable()
                                : true;

                            return available ? { type: wallet.type, component: component, label: wallet.label } : null;
                        } catch (e) {
                            Utilities.log(e);

                            return null;
                        }
                    });

                    (await Promise.all(probes)).filter(Boolean).forEach((wallet) => {
                        this.flowComponentInstances[wallet.type] = {
                            component: wallet.component,
                            containerId: 'flow-apm-' + wallet.type
                        };
                        this.availableApms.push({ type: wallet.type, label: wallet.label });
                    });
                },

                /**
                 * Surface, in the Checkout.com server log, any APM enabled in admin (Alternative
                 * Payments for Flow) that Checkout.com reports as unavailable — so engineers can see
                 * the config vs. availability mismatch in their logging tools.
                 *
                 * @param {string[]} unavailable - APM types enabled in admin but not available
                 */
                logUnavailableApms: function (unavailable) {
                    if (!unavailable || !unavailable.length) {
                        return;
                    }

                    const message = 'APMs enabled in admin (Alternative Payments for Flow) but reported '
                        + 'unavailable by Checkout.com (isAvailable=false): ' + unavailable.join(', ');

                    // Browser console (when console_logging is enabled).
                    Utilities.log(message);

                    // Server-side Checkout.com log channel, so it appears in engineers' logging tools.
                    try {
                        const formKey = (document.querySelector('input[name="form_key"]') || {}).value;
                        const url = Url.build('checkout_com/flow/log')
                            + (formKey ? '?form_key=' + encodeURIComponent(formKey) : '');

                        fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                level: 'warning',
                                message: message,
                                context: { unavailable: unavailable }
                            })
                        }).catch((e) => Utilities.log(e));
                    } catch (e) {
                        Utilities.log(e);
                    }
                },

                /**
                 * Friendly label for an APM type (falls back to a title-cased type).
                 *
                 * @param {string} type
                 * @returns {string}
                 */
                apmLabel: function (type) {
                    const labels = {
                        ideal: 'iDEAL',
                        sepa: 'SEPA Direct Debit',
                        eps: 'EPS',
                        bancontact: 'Bancontact',
                        knet: 'KNET',
                        multibanco: 'Multibanco',
                        p24: 'Przelewy24',
                        klarna: 'Klarna',
                        alipay_cn: 'Alipay CN',
                        alipay_hk: 'Alipay HK',
                        dana: 'DANA',
                        gcash: 'GCash',
                        tng: "Touch 'n Go",
                        truemoney: 'TrueMoney',
                        kakaopay: 'Kakao Pay',
                        stcpay: 'STC Pay',
                        benefit: 'Benefit',
                        qpay: 'Qpay',
                        mbway: 'MB WAY',
                        alma: 'Alma',
                        tamara: 'Tamara',
                        tabby: 'Tabby',
                        twint: 'TWINT',
                        plaid: 'Pay by Bank',
                        vipps: 'Vipps',
                        mobilepay: 'MobilePay',
                        bizum: 'Bizum',
                        wechatpay: 'WeChat Pay',
                        paynow: 'PayNow',
                        octopus: 'Octopus',
                        swish: 'Swish',
                        blik: 'BLIK'
                    };

                    return labels[type] || (type.charAt(0).toUpperCase() + type.slice(1));
                },

                /**
                 * Returns an inline SVG glyph for a method row (shown in the icon chip next to the
                 * label), matching the native Flow list layout. These are simple generic glyphs
                 * (card / wallet / bank); to use official brand artwork, drop SVG/PNG assets in the
                 * module and return an <img> here instead. The official branded button still renders
                 * below the row when the method is selected.
                 *
                 * @param {string} type
                 * @returns {string} inline SVG markup
                 */
                methodIcon: function (type) {
                    // Wallets use the official brand logo images shipped in the module
                    // (view/frontend/web/images/flow). Card / APMs keep a simple generic glyph.
                    const logos = {
                        googlepay: 'icon-googlepay.png',
                        applepay: 'icon-applepay.png',
                        paypal: 'icon-paypal.png'
                    };
                    const imagesPath = window.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_data?.images_path;

                    if (logos[type] && imagesPath) {
                        return '<img class="cko-method-logo" alt="" src="' + imagesPath + '/' + logos[type] + '" />';
                    }

                    const card = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6">'
                        + '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>';
                    const bank = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6">'
                        + '<path d="M3 9l9-5 9 5"/><path d="M4 9v9M9 9v9M15 9v9M20 9v9"/><path d="M2 20h20"/></svg>';

                    return type === 'card' ? card : bank;
                },

                /**
                 * Mount the selected method's component into its container (once). Containers are
                 * shown/hidden by the KO `visible` binding tied to selectedFlowMethod.
                 *
                 * @param {string} method - 'card' | 'googlepay' | 'applepay'
                 */
                mountSelected: function (method) {
                    if (!method || !this.flowComponentInstances) {
                        return;
                    }

                    const inst = this.flowComponentInstances[method];

                    if (inst && !this.mountedComponents[method]) {
                        const container = document.getElementById(inst.containerId);

                        if (container) {
                            inst.component.mount(container);
                            this.mountedComponents[method] = inst.component;
                            this.flowComponents[method] = inst.component;
                        }
                    }
                },

                /**
                 * Unmount every mounted Flow component (used on reload / country change).
                 */
                unmountAllComponents: function () {
                    if (this.mountedComponents) {
                        Object.keys(this.mountedComponents).forEach((type) => {
                            try {
                                this.mountedComponents[type].unmount();
                            } catch (e) {
                                Utilities.log(e);
                            }
                        });
                    }

                    this.mountedComponents = {};
                    this.flowComponents = {};
                },

                /**
                 * handleSubmit: place order first to get reference, then submit payment to Checkout.com
                 * with session_data + reference so the payment is linked to the order.
                 * @param {Object} flowSelf - Flow component instance (has type / selectedType)
                 * @param {Object} submitData - From Flow, contains session_data
                 * @returns {Promise<Object>} Checkout.com API response (unmodified for Flow)
                 */
                submitPaymentWithReference: function (flowSelf, submitData) {
                    const self = this;
                    const selectedType = (flowSelf && (flowSelf.type || flowSelf.selectedType)) || 'card';
                    const payload = {
                        methodId: METHOD_ID,
                        selectedMethod: selectedType
                    };

                    if (!AdditionalValidators.validate()) {
                        FullScreenLoader.stopLoader();

                        return Promise.reject(new Error('Validation failed'));
                    }

                    FullScreenLoader.startLoader();

                    const has3DS = this.get3DSInfos(selectedType);

                    return Utilities.placeOrder(payload, METHOD_ID, false, has3DS)
                        .then(function (orderResponse) {
                            if (!orderResponse || !orderResponse.success) {
                                FullScreenLoader.stopLoader();
                                if (orderResponse && orderResponse.message) {
                                    self.showMessage('error', orderResponse.message, METHOD_ID);
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
                            const submitUrl = Url.build('checkout_com/flow/submit') + (formKey ? '?form_key=' + encodeURIComponent(formKey) : '');
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
                            return submitResponse.json().then(function (data) {
                                if (!submitResponse.ok || data.error) {
                                    FullScreenLoader.stopLoader();
                                    self.showMessage('error', data.message || 'Payment submit failed', METHOD_ID);

                                    return Promise.reject(data);
                                }
                                return data;
                            });
                        });
                },

                /**
                 * Get 3DS infos from checkoutConfig for current method
                 * @param {string} type - Payment method type
                 * @returns {boolean}
                 */
                get3DSInfos: function (type) {
                    if (this.methodNameMap[type]) {
                        type = this.methodNameMap[type];
                    }

                    let methodType = 'checkoutcom_' + type;
                    let methodInformations = window.checkoutConfig.payment.checkoutcom_magento2[methodType];

                    if (!methodInformations) {
                        return false;
                    }

                    return !!(methodInformations.three_ds && methodInformations.three_ds === '1');
                },

                /**
                 * Send Event for saveCard
                 * @param selectedType
                 */
                sendSaveCardEvent: function (selectedType = null) {
                    this.currentMethod = selectedType;

                    const cardEvent = new CustomEvent("saveCard", {
                        detail: {
                            method: this.currentMethod
                        }
                    });

                    document.querySelector('body').dispatchEvent(cardEvent);
                }
            }
        );
    }
);
