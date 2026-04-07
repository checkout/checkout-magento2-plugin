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
        'flowjs',
        "CheckoutCom_Magento2/js/common/view/payment/utilities",
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, ko, Component, Customer, Url, CheckoutWebComponents, Utilities, AdditionalValidators, FullScreenLoader, StepNavigator, Quote) {
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
                    flowComponent: null,
                    isLoading: false,
                    methodNameMap: {
                        'card' : 'card_payment',
                        'googlepay' : 'google_pay',
                        'applepay' : 'apple_pay'
                    },
                    currentMethod: null,
                    currentCountryCode: null,
                },
                reference: null,

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    window.currentGrandTotal = Quote.totals().base_grand_total;

                    this._super();

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
                    this.getFlowContextData();

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
                        if (Utilities.methodIsSelected(METHOD_ID) && Quote.totals().base_grand_total !== window.currentGrandTotal) {
                            this.reloadFlow();
                            window.currentGrandTotal = Quote.totals().base_grand_total;
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

                        if (this.flowComponent) {
                            this.flowComponent.unmount();
                        }

                        this.getFlowContextData();
                    }
                },

                /**
                 * Gets the module images path
                 */
                getImagesPath: function () {
                    return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path;
                },

                placeOrder: function () {
                    if (Utilities.methodIsSelected(METHOD_ID)) {
                        this.flowComponent.submit();
                    }
                },

                /**
                 * Get context data from API
                 * @returns {Promise<void>}
                 */
                getFlowContextData: async function () {
                    try {
                        const response = await fetch(Url.build('checkout_com/flow/prepare'), {method: "GET"});
                        const data = await response.json();

                        if (!response.ok) {
                            this.showErrorMessage();
                        } else {
                            await this.initComponent(data);
                        }
                    } catch (e) {
                        this.showErrorMessage(e);
                    } finally {
                        this.isLoading = false;
                    }
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
                 * Init Flow Component with API response
                 * @param data
                 * @returns {Promise<void>}
                 */
                initComponent: async function (data) {
                    let self = this;

                    this.allowPlaceOrder(false);

                    const paymentSession = data.paymentSession;
                    const publicKey = data.publicKey;
                    let appearance  = data.appearance;
                    this.paymentSessionId = paymentSession?.id || (paymentSession && paymentSession.id) || null;

                    if (appearance !== "") {
                        try {
                            appearance = JSON.parse(appearance);
                        } catch (e) {
                            Utilities.log(e);
                            appearance = "";
                        }
                    }

                    const checkout = await CheckoutWebComponents({
                        paymentSession,
                        publicKey,
                        environment: data.environment,
                        appearance,
                        componentOptions: {
                            flow: {
                                showPayButton: false
                            },
                            card: {
                                displayCardholderName: this.shouldDisplayCardholderName()
                            }
                        },
                        onReady: (_self) => {
                            if (!this.currentMethod) {
                                this.sendSaveCardEvent(_self.selectedType);
                            }
                        },
                        onChange: (component) => {
                            if (component.isValid()) {
                                this.allowPlaceOrder(true);
                            } else {
                                this.allowPlaceOrder(false);
                            }

                            if (this.currentMethod && this.currentMethod !== component.selectedType) {
                                this.sendSaveCardEvent(component.selectedType);
                            }
                        },
                        onError: (component, error) => {
                            const payment_id = error.details?.paymentSessionId;

                            Utilities.showMessage('error', 'Could not finalize the payment.', METHOD_ID);
                            Utilities.log("Error with payment method " + component.type, error);
                            FullScreenLoader.stopLoader();

                            if (payment_id) {
                                Utilities.redirectFailedPayment(payment_id, this.reference);
                            }
                        }
                    });

                    let flowContainer = this.getContainer();

                    this.flowComponent = checkout.create('flow',{
                        handleSubmit: async (_self, submitData) => {
                            return self.submitPaymentWithReference(_self, submitData);
                        },
                        onPaymentCompleted: async (_self, paymentResponse) => {
                            if  (paymentResponse.status === "Approved") {
                                Utilities.redirectCompletedPayment(paymentResponse.id, this.reference);
                            }
                            FullScreenLoader.stopLoader();
                        },
                    });

                    this.flowComponent.mount(flowContainer);
                },

                /**
                 * Get container from DOM
                 * @returns {HTMLElement}
                 */
                getContainer: function() {
                    let flowContainer = document.getElementById('flow-container');

                    if (!flowContainer) {
                        const actions = Utilities.getMethodContainer(METHOD_ID).find('.payment-method-content .action-toolbar');

                        flowContainer = document.createElement('div');
                        flowContainer.id = 'flow-container-dynamic';
                        actions.prepend(flowContainer);
                    }

                    return flowContainer;
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
                },

                shouldDisplayCardholderName: function() {
                    let displayCardholderName = window.checkoutConfig?.payment?.checkoutcom_magento2?.checkoutcom_card_payment?.display_cardholder_name;

                    return Number(displayCardholderName) === 0 ? 'hidden' : 'top';
                }
            }
        );
    }
);
