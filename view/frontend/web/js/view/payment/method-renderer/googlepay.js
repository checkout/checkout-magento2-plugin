/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/method-renderer/cc-form',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'mage/url',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate',
        'mage/cookies',
        'gpay'
    ],
    function($, Component, CheckoutCom, quote, globalMessages, url, setPaymentInformationAction, fullScreenLoader, additionalValidators, checkoutData, addressConverter, redirectOnSuccessAction, t, customer) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/googlepay',
                code: 'checkout_com_googlepay',
                card_token_id: null,
                button_target: '#ckoGooglePayButton',
                debug: false
            },

            /**
             * @returns {exports}
             */
            initialize: function(config, messageContainer) {
                this._super();
                this.initObservable();
                this.messageContainer = messageContainer || config.messageContainer || globalMessages;
                this.setEmailAddress();

                return this;
            },

            /**
             * @returns {exports}
             */
            initObservable: function () {
                this._super()
                    .observe('isHidden');

                return this;
            },

            /**
             * @returns {bool}
             */
            isVisible: function () {
                return this.isHidden(this.messageContainer.hasMessages());
            },

            /**
             * @returns {bool}
             */
            removeAll: function () {
                this.messageContainer.clear();
            },

            /**
             * @returns {void}
             */
            onHiddenChange: function (isHidden) {
                var self = this;
                // Hide message block if needed
                if (isHidden) {
                    setTimeout(function () {
                        $(self.selector).hide('blind', {}, 500)
                    }, 10000);
                }
            },

            /**
             * @returns {string}
             */
            getCode: function() {
                return CheckoutCom.getCodeGooglePay();
            },

            /**
             * @returns {string}
             */
            getGooglePayTitle: function() {
                return CheckoutCom.getPaymentConfigGooglePay()['title'];
            },

            /**
             * @returns {bool}
             */
            isActive: function() {
                return CheckoutCom.getPaymentConfigGooglePay()['isActive'];
            },

            /**
             * @returns {string}
             */
            getEmailAddress: function() {
                return window.checkoutConfig.customerData.email || quote.guestEmail || checkoutData.getValidatedEmailValue();
            },

            /**
             * @returns {void}
             */
            setEmailAddress: function() {
                var email = this.getEmailAddress();
                $.cookie('ckoEmail', email);
            },

            /**
             * @returns {string}
             */
            getPublicKey: function() {
                return CheckoutCom.getPaymentConfig()['public_key'];
            },

            /**
             * @returns {string}
             */
            getQuoteValue: function() {
                return (quote.getTotals()().grand_total).toFixed(2);
            },

            /**
             * @returns {string}
             */
            getQuoteCurrency: function() {
                return CheckoutCom.getPaymentConfig()['quote_currency'];
            },

            /**
             * @returns {object}
             */
            getBillingAddress: function() {
                return quote.billingAddress();
            },

            /**
             * @returns {array}
             */
            getLineItems: function() {
                return [];
            },

            /**
             * @returns {array}
             */
            getAllowedNetworks: function() {
                return CheckoutCom.getPaymentConfigGooglePay()['allowedNetworks'].split(',');
            },

            /**
             * @returns {void}
             */
            logEvent: function(data) {
                if (this.debug === true) {
                    console.log(data);
                }
            },

            /**
             * @returns {bool}
             */
            launchGooglePay: function() {
                // Prepare the parameters
                var gp = CheckoutCom.getPaymentConfigGooglePay();
                var self = this;

                //  Button click event
                $(self.button_target).click(function(evt) {
                    // Validate T&C submission
                    if (!additionalValidators.validate()) {
                        return;
                    }

                    // Prepare the payment parameters
                    var allowedPaymentMethods = ['CARD', 'TOKENIZED_CARD'];
                    var allowedCardNetworks = self.getAllowedNetworks();
            
                    var tokenizationParameters = {
                        tokenizationType: 'PAYMENT_GATEWAY',
                        parameters: {
                            'gateway': gp['gatewayName'],
                            'gatewayMerchantId': self.getPublicKey()
                        }
                    }

                    // Prepare the Google Pay client
                    onGooglePayLoaded();
         
                    /**
                     * Show Google Pay chooser when Google Pay purchase button is clicked
                     */
                    var paymentDataRequest = getGooglePaymentDataConfiguration();
                    paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
        
                    var paymentsClient = getGooglePaymentsClient();
                    paymentsClient.loadPaymentData(paymentDataRequest)
                    .then(function (paymentData) {
                        // handle the response
                        processPayment(paymentData);
                    })
                    .catch(function (err) {
                        self.logEvent(err);
                    });

                    /**
                     * Initialize a Google Pay API client
                     *
                     * @returns {google.payments.api.PaymentsClient} Google Pay API client
                     */
                    function getGooglePaymentsClient() {
                        return (new google.payments.api.PaymentsClient({
                            environment: gp['environment'] 
                        }));
                    }
            
                    /**
                     * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
                     */
                    function onGooglePayLoaded() {
                        var paymentsClient = getGooglePaymentsClient();
                        paymentsClient.isReadyToPay({ allowedPaymentMethods: allowedPaymentMethods })
                        .then(function (response) {
                            if (response.result) {
                                prefetchGooglePaymentData();
                            }
                        })
                        .catch(function (err) {
                            self.logEvent(err);
                        });
                    }
            
                    /**
                     * Configure support for the Google Pay API
                     *
                     * @see {@link https://developers.google.com/pay/api/web/reference/object#PaymentDataRequest|PaymentDataRequest}
                     * @returns {object} PaymentDataRequest fields
                     */
                    function getGooglePaymentDataConfiguration() {
                        return {
                            // @todo a merchant ID is available for a production environment after approval by Google
                            // @see {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/overview|Test and deploy}
                            merchantId: gp['merchantId'],
                            paymentMethodTokenizationParameters: tokenizationParameters,
                            allowedPaymentMethods: allowedPaymentMethods,
                            cardRequirements: {
                                allowedCardNetworks: allowedCardNetworks
                            }
                        };
                    }
            
                    /**
                     * Provide Google Pay API with a payment amount, currency, and amount status
                     *
                     * @see {@link https://developers.google.com/pay/api/web/reference/object#TransactionInfo|TransactionInfo}
                     * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
                     */
                    function getGoogleTransactionInfo() {
                        return {
                            currencyCode: CheckoutCom.getPaymentConfig()['quote_currency'],
                            totalPriceStatus: 'FINAL',
                            totalPrice: self.getQuoteValue()
                        };
                    }
            
                    /**
                     * Prefetch payment data to improve performance
                     */
                    function prefetchGooglePaymentData() {
                        var paymentDataRequest = getGooglePaymentDataConfiguration();

                        // transactionInfo must be set but does not affect cache
                        paymentDataRequest.transactionInfo = {
                            totalPriceStatus: 'NOT_CURRENTLY_KNOWN',
                            currencyCode: CheckoutCom.getPaymentConfig()['quote_currency']
                        };

                        var paymentsClient = getGooglePaymentsClient();
                        paymentsClient.prefetchPaymentData(paymentDataRequest);
                    }
            
                    /**
                     * Process payment data returned by the Google Pay API
                     *
                     * @param {object} paymentData response from Google Pay API after shopper approves payment
                     * @see {@link https://developers.google.com/pay/api/web/reference/object#PaymentData|PaymentData object reference}
                     */
                    function processPayment(paymentData) {
                        self.logEvent(JSON.parse(paymentData.paymentMethodToken.token));
                        $.post(
                            "server.php",
                            {
                                signature: JSON.parse(paymentData.paymentMethodToken.token).signature,
                                protocolVersion: JSON.parse(paymentData.paymentMethodToken.token).protocolVersion,
                                signedMessage: JSON.parse(paymentData.paymentMethodToken.token).signedMessage,
                            },
                            function (data, status) {
                                alert("Response: \n" + data);
                            }
                        );
                    }
                });
            },
        });
    }
);