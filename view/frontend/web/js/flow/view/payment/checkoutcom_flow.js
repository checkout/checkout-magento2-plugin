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
        "CheckoutCom_Magento2/js/common/view/payment/utilities"
    ],
    function ($, ko, Component, Customer, Url, CheckoutWebComponents, Utilities) {
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
                    // todo gerer les event
                    var self = this;

                    // Option click event
                    $('.payment-method input[type="radio"]').on('click', function () {
                        self.allowPlaceOrder(false);
                    });
                },

                /**
                 * Gets the module images path
                 */
                getImagesPath: function () {
                    return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path;
                },

                placeOrder: function () {
                    // todo
                    console.log('place')
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
                    const paymentSession = data.paymentSession;
                    const publicKey = data.publicKey;

                    let appearance  = data.appearance;

                    if (appearance !== "") {
                        appearance  = JSON.parse(data.appearance);
                    }

                    const checkout = await CheckoutWebComponents({
                        paymentSession,
                        publicKey,
                        environment: data.environment,
                        appearance
                    });

                    let flowContainer = document.getElementById('flow-container');

                    if (!flowContainer) {
                        const actions = document.getElementById('checkoutcom_flow_container').find('.payment-method-content .action-toolbar');

                        flowContainer = document.createElement('div');
                        flowContainer.id = 'flow-container-dynamic';
                        actions.prepend(flowContainer);
                    }

                    const flowComponent = checkout.create("flow");

                    flowComponent.mount(flowContainer);
                }
            }
        );
    }
);
