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
        'jquery/ui'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, Quote, __) {

        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_apm';
        let loadEvents = true;
        let loaded = false;

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.html',
                    redirectAfterPlaceOrder: false
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    this._super();
                    Utilities.loadCss('apm', 'apm');
                },

                /**
                 * @return {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @return {string}
                 */
                getValue: function (field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * @return {void}
                 */
                checkLastPaymentMethod: function () {
                    return Utilities.checkLastPaymentMethod();
                },

                /**
                 * @return {void}
                 */
                initWidget: function () {
                    // Start the loader
                    FullScreenLoader.startLoader();

                    let self = this;

                    // Send the AJAX request
                    $.ajax(
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
                    );
                },

                /**
                 * @return {void}
                 */
                initEvents: function () {
                    if (loadEvents) {
                        let self = this;
                        let prevAddress;

                        Quote.billingAddress.subscribe(
                            function (newAddress) {
                                if (!newAddress || !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                                    prevAddress = newAddress;
                                    if (newAddress) {
                                        self.reloadApms(Quote.billingAddress().countryId);
                                    }
                                }
                            }
                        );

                        loadEvents = false;
                    }
                },

                reloadApms: function (countryId) {
                    let self = this;

                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Send the AJAX request
                    $.ajax(
                        {
                            type: "POST",
                            url: Utilities.getUrl('apm/display'),
                            data: {
                                country_id: countryId
                            },
                            success: function (data) {
                                self.animateRender(data);
                                // Auto select the previous method
                                self.checkLastPaymentMethod();
                            },
                            error: function (request, status, error) {
                                Utilities.log(error);

                                // Stop the loader
                                FullScreenLoader.stopLoader();
                            }
                        }
                    );
                },

                /**
                 * Animate opening of APM accordion
                 */
                animateRender: function (data) {
                    $('#apm-container').empty().hide();
                    if ($('#apm-container').hasClass("ui-accordion")) {
                        $('#apm-container').accordion("destroy");
                    }

                    $('#apm-container').append(data.html)
                        .accordion(
                            {
                                heightStyle: 'content',
                                animate: {
                                    duration: 200
                                }
                            }
                        );
                    if (data.apms.includes('klarna') == false) {

                        // Stop the loader
                        $('#apm-container').show();
                        FullScreenLoader.stopLoader();
                    }
                },

                /**
                 * @return {void}
                 */
                placeOrder: function () {
                    let id = $("#apm-container div[aria-selected=true]").attr('id');

                    if (Utilities.methodIsSelected(METHOD_ID) && id) {
                        let form = $("#cko-apm-form-" + id),
                            data = {methodId: METHOD_ID};

                        // Start the loader
                        FullScreenLoader.startLoader();

                        // Serialize data
                        form.serializeArray().forEach(
                            function (e) {
                                data[e.name] = e.value;
                            }
                        );

                        // Place the order
                        if (AdditionalValidators.validate() && form.valid() && this.custom(data)) {
                            Utilities.placeOrder(
                                data,
                                METHOD_ID,
                                function () {
                                    Utilities.log(__('Success'));
                                },
                                function () {
                                    Utilities.log(__('Fail'));
                                }
                            );
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
                custom: function (data) {
                    var result = true;
                    if (typeof this[data.source] == 'function') {
                        result = this[data.source](data);
                    }

                    return result;
                },

                /**
                 * @return {boolean}
                 */
                klarna: function (data) {
                    try {
                        Klarna.Payments.authorize(
                            {
                                instance_id: "klarna-payments-instance",
                                auto_finalize: true
                            },
                            {},
                            function (response) {
                                data.authorization_token = response.authorization_token;
                                Utilities.placeOrder(
                                    data,
                                    METHOD_ID,
                                    function () {
                                        Utilities.log(__('Success'));
                                    },
                                    function () {
                                        Utilities.showMessage('error', 'Could not finalize the payment.', METHOD_ID);
                                    }
                                );
                            }
                        );
                    } catch (e) {
                        Utilities.showMessage('error', 'Could not finalize the payment.', METHOD_ID);
                        Utilities.log(e);
                    }

                    return false;
                },

                /**
                 * @return {boolean}
                 */
                sepa: function (data) {
                    return data.hasOwnProperty('accepted');
                }
            }
        );
    }
);
