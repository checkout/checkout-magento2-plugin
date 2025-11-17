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
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function ($, ko, Component, Customer, Url, CheckoutWebComponents, Utilities, AdditionalValidators, FullScreenLoader, StepNavigator, RedirectOnSuccessAction) {
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
                    methodNameMap: {
                        'card' : 'card_payment',
                        'googlepay' : 'google_pay',
                        'applepay' : 'apple_pay'
                    },
                    currentMethod: null,
                    currentCountryCode: null
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
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
                    this.setCountryCode();
                    this.sendSaveCardEvent();

                    this.flowComponent.unmount();

                    this.getFlowContextData();
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
                    }
                },

                showErrorMessage: function (message = null) {
                    Utilities.getMethodContainer(METHOD_ID).css('display','none');
                    Utilities.showGlobalMessage('error','Error with Flow payment method');
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
                            const payment_id = error.details && error.details.paymentId;

                            Utilities.showMessage('error', 'Could not finalize the payment.', METHOD_ID);
                            Utilities.log("Error with payment method " + component.type, error);
                            FullScreenLoader.stopLoader();

                            Utilities.redirectFailedPayment(payment_id);
                        }
                    });

                    let flowContainer = this.getContainer();


                    this.flowComponent = checkout.create('flow',{
                        onSubmit: (_self) => {
                            self.saveOrder(_self.type);
                        },
                        onPaymentCompleted: async (_self, paymentResponse) => {
                            // Handle synchronous payment
                            if  (paymentResponse.status === "Approved") {
                                RedirectOnSuccessAction.execute();
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
                 * Save Order after submit component
                 * @param type
                 */
                saveOrder: function (type) {
                    const data = {
                        methodId: METHOD_ID,
                        selectedMethod: type
                    };
                    const has3DS = this.get3DSInfos(type);

                    // Place the order
                    if (AdditionalValidators.validate()) {
                        Utilities.placeOrder(
                            data,
                            METHOD_ID,
                            true,
                            has3DS,
                            function() {
                                Utilities.log(__('Success'));
                            },
                            function() {
                                Utilities.log(__('Fail'));
                            },
                        );
                        Utilities.cleanCustomerShippingAddress();
                    }
                },

                /**
                 * Get 3DS infos from checkoutConfig for current method
                 * @param type
                 * @returns {boolean}
                 */
                get3DSInfos: function(type) {
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
