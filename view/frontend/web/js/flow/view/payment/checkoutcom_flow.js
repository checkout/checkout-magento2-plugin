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
    ],
    function ($, ko, Component, Customer, Url, CheckoutWebComponents, Utilities, AdditionalValidators, FullScreenLoader) {
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
                    currentMethod: null
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    this._super();

                    this.getFlowContextData();

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
                    // Listen for saveCard event
                    document.querySelector('body').addEventListener(
                        "askPaymentMethod",
                        () => {
                            this.sendSaveCardEvent(this.currentMethod);
                        },
                    );
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

                toggleTooltip: function () {
                    // todo à garder ?
                    this.tooltipVisible(!this.tooltipVisible());
                },

                /**
                 * @param {object} billingAddress
                 */
                checkBillingAdressCustomerName: function (billingAddress) {
                    // todo
                    const valid = billingAddress !== null;
                },

                /**
                 * Get context data from API
                 * @returns {Promise<void>}
                 */
                getFlowContextData: async function () {
                    const response = await fetch(Url.build('checkout_com/flow/prepare'), {method: "GET"});
                    const data = await response.json();

                    if (!response.ok) {
                        Utilities.log('Error creating payment session for flow');
                    } else {
                        await this.initComponent(data)
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
                            Utilities.showMessage('error', 'Could not finalize the payment.', METHOD_ID);
                            Utilities.log("Error with payment method " + component.type, error);
                        },
                    });

                    let flowContainer = this.getContainer();

                    this.flowComponent = checkout.create('flow',{
                        onSubmit: (_self) => {
                            self.saveOrder(_self.type);
                        }
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
                        const actions = document.getElementById('checkoutcom_flow_container').find('.payment-method-content .action-toolbar');

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

                    return !!(methodInformations.three_ds && methodInformations.three_ds === '1');
                },

                /**
                 * Send Event for saveCard
                 * @param selectedType
                 */
                sendSaveCardEvent: function (selectedType) {
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
