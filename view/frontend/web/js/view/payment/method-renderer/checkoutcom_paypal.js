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
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'jquery/ui',
    ],
    function(
        $, Component, Utilities, FullScreenLoader, AdditionalValidators, Quote,
        __) {

        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_paypal';
        let loadEvents = true;
        let loaded = false;

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID +
                        '.html',
                    buttonId: METHOD_ID + '_btn',
                    redirectAfterPlaceOrder: false,
                    chkPayPalOrderid: null,
                    chkPayPalContextId: null,
                },

                /**
                 * @return {exports}
                 */
                initialize: function() {
                    this._super();
                    Utilities.loadCss('paypal', 'paypal');
                    let self = this;

                    //Todo: proper way to init, the paypal button must be loaded when
                    // #paypal-button-container is on the dom
                    setTimeout(function() {
                        self.initPaypalButton();
                    }, 1000);
                },

                initPaypalButton: function() {

                    let self = this;

                    let containerSelector = '#paypal-button-container';
                    let datas = {};

                    // Prepare Context
                    if ($(containerSelector).length > 0) {
                        $.ajax(
                            {
                                type: 'POST',
                                url: Utilities.getUrl('paypal/context'),
                                showLoader: true,
                                data: datas,
                                success: function(data) {
                                    if (typeof data.content !== 'undefined') {
                                        self.chkPayPalOrderid = data.content.partner_metadata.order_id;
                                        self.chkPayPalContextId = data.content.id;

                                        // Init paypal button after getting context order id
                                        paypal.Buttons({
                                            createOrder() {
                                                return self.chkPayPalOrderid;
                                            },
                                            onApprove: async function(data) {
                                                self.placeOrder();
                                            },
                                        }).render(containerSelector);

                                    }
                                    // Todo else message manager error
                                },
                                error: function(request, status, error) {
                                    // Todo message manager error
                                },
                            },
                        );
                    }
                },

                /**
                 * @return {string}
                 */
                getCode: function() {
                    return METHOD_ID;
                },

                /**
                 * @return {string}
                 */
                getValue: function(field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * @return {void}
                 */
                checkLastPaymentMethod: function() {
                    return Utilities.checkLastPaymentMethod();
                },

                /**
                 * @return {void}
                 */
                placeOrder: function() {
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
                                function() {
                                    Utilities.log(__('Success'));
                                },
                                function() {
                                    Utilities.log(__('Fail'));
                                },
                            );
                            Utilities.cleanCustomerShippingAddress();
                        }

                        FullScreenLoader.stopLoader();
                    }
                },
            },
        );
    },
);
