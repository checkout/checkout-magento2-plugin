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
                    orderId: null
                },

                /**
                 * @return {exports}
                 */
                initialize: function() {
                    this._super();
                    Utilities.loadCss('paypal', 'paypal');
                },

                initPaypalButton: function() {

                    let self = this;

                    // Obtain Checkout context OrderId
                    $.ajax(
                        {
                            type: "POST",
                            url: Utilities.getUrl('paypal/context'),
                            data: {
                            },
                            success: function (data) {
                                alert('success');
                            },
                            error: function (request, status, error) {
                                alert('passuccess');
                            }
                        }
                    );


                    paypal.Buttons({
                        createOrder() {

                            return self.defaults.orderId;
                        },
                        onApprove: async function(data) {
                            alert(
                                'Transaction approved, PLEASE MAKE something');
                        },
                    }).render('#paypal-button-container');
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
                initWidget: function() {
                    // Start the loader
                    FullScreenLoader.startLoader();

                    let self = this;
                    alert('inipaypal');
                    // Send the AJAX request
                    /*$.ajax(
                        {
                            type: "POST",
                            url: Utilities.getUrl('apm/display'),
                            data: {
                                country_id: Utilities.getBillingAddress() ? Utilities.getBillingAddress().country_id : null
                            },
                            success: function (data) {
                                self.animateRender(data);
                                self.initEvents();
                                self.checkLastPaymentMethod();
                            },
                            error: function (request, status, error) {
                                Utilities.log(error);

                                // Stop the loader
                                FullScreenLoader.stopLoader();
                            }
                        }
                    );*/
                },

                /**
                 * @return {void}
                 */
                initEvents: function() {
                    alert('ça init les events');
                    if (loadEvents) {
                        let self = this;
                        let prevAddress;

                        /*Quote.billingAddress.subscribe(
                            function (newAddress) {
                                if (!newAddress || !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                                    prevAddress = newAddress;
                                    if (newAddress) {
                                        self.reloadApms(Quote.billingAddress().countryId);
                                    }
                                }
                            }
                        );*/

                        loadEvents = false;
                    }
                },

                /**
                 * @return {void}
                 */
                placeOrder: function() {
                    alert('place paypal order');

                    let id = $('#apm-container div[aria-selected=true]').
                        attr('id');

                    if (Utilities.methodIsSelected(METHOD_ID) && id) {
                        let form = $('#cko-apm-form-' + id),
                            data = {methodId: METHOD_ID};

                        // Start the loader
                        FullScreenLoader.startLoader();

                        // Serialize data
                        form.serializeArray().forEach(
                            function(e) {
                                data[e.name] = e.value;
                            },
                        );

                        // Place the order
                        if (AdditionalValidators.validate() && form.valid() &&
                            this.custom(data)) {
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

                /**
                 * Custom "before place order" flows.
                 */

                /**
                 * Dynamic function handler.
                 *
                 * @param  {String}   id      The identifier
                 * @return {boolean}
                 */
                custom: function(data) {
                    var result = true;
                    if (typeof this[data.source] == 'function') {
                        result = this[data.source](data);
                    }

                    return result;
                },
            },
        );
    },
);
