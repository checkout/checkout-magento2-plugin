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
        'jquery',
        "CheckoutCom_Magento2/js/common/view/payment/utilities",
        'Magento_Checkout/js/model/full-screen-loader',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer'
    ],
    function (ko, $, Utilities, FullScreenLoader, Component, Quote, Customer) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'CheckoutCom_Magento2/flow/view/save-card.html',
                containerSelector: '#checkoutcom_flow_container',
                checkboxSelector: '[name="flow_save_card"]',
                isVisible: ko.observable(false),
                relatedMethod: 'checkoutcom_card_payment',
                isSavedCard: false,
                checkoutConfig: window.checkoutConfig.payment.checkoutcom_magento2,
                saveCardConfig: false
            },
            initialize: function () {
                this._super();
                this.saveCardConfig = this.getSaveCardConfig();

                if (!!(this.saveCardConfig && this.saveCardConfig === '1') && this.isLoggedIn()) {
                    this.initListeners();
                }

                return this;
            },
            /**
             * Get config from window object
             * @returns {*}
             */
            getSaveCardConfig: function () {
                return Utilities.getValue(this.relatedMethod, 'save_card_option');
            },

            /**
             * @return {boolean}
             */
            isLoggedIn: function () {
                return Customer.isLoggedIn();
            },

            /**
             * Init Listeners for quote payment method's changes and saveCard Events
             */
            initListeners: function () {
                let self = this;

                Quote.paymentMethod.subscribe(function (method) {
                    self.updateComponent(method)
                }, null, 'change');

                document.querySelector('body').addEventListener(
                    "saveCard",
                    (e) => {
                        this.isVisible(e.detail.method === "card");

                        if (this.isSavedCard && this.isVisible() === false) {
                            self.sendSaveCardInfo(false);
                        }
                    },
                );

                $('body').on(
                    'click',
                    this.containerSelector + ' ' + this.checkboxSelector,
                    function () {
                        self.sendSaveCardInfo(this.checked);
                    }
                );
            },

            /**
             * Update component info depending of payment method
             * @param method
             */
            updateComponent: function (method) {
                if (method.method !== 'checkoutcom_flow') {
                    this.isVisible(false);

                    if (this.isSavedCard) {
                        this.isSavedCard = false;
                        this.sendSaveCardInfo(false);
                    }
                } else {
                    this.getFlowCurrentMethod();
                }
            },

            /**
             * Send Save card info to backend
             * @param isChecked
             */
            sendSaveCardInfo: function (isChecked) {
                this.isSavedCard = isChecked;

                $.ajax(
                        {
                            type: "POST",
                            url: Utilities.getUrl("flow/saveCard"),
                            data: {
                                save: this.isSavedCard ? 1 : 0
                            },
                            error: function (request, status, error) {
                                Utilities.log(error);
                            }
                        }
                    );
            },

            /**
             * Send event to get Flow Component current method when changing to Flow payment
             */
            getFlowCurrentMethod: function () {
                const askPaymentMethod = new Event("askPaymentMethod");

                document.querySelector('body').dispatchEvent(askPaymentMethod);
            }
        });
    }
);
