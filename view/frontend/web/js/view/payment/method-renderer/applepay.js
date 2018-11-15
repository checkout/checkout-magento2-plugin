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
        'mage/url',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate'
    ],
    function($, Component, CheckoutCom, quote, url, setPaymentInformationAction, fullScreenLoader, additionalValidators, checkoutData, addressConverter, redirectOnSuccessAction, t, customer) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/applepay',
                code: 'checkout_com_applepay',
                card_token_id: null,
                redirectAfterPlaceOrder: true, 
                button_target: '#cko-applepay-holder button'
            },

            /**
             * @returns {exports}
             */
            initialize: function() {
                this._super();

                return this;
            },

            /**
             * @returns {string}
             */
            getCode: function() {
                return CheckoutCom.getCodeApplePay();
            },

            /**
             * @returns {string}
             */
            getApplePayTitle: function() {
                return CheckoutCom.getPaymentConfigApplePay()['title'];
            },

            /**
             * @param {string} card_token_id
             */
            setCardTokenId: function(card_token_id) {
                this.card_token_id = card_token_id;
            },

            /**
             * @returns {bool}
             */
            isActive: function() {
                return CheckoutCom.getPaymentConfigApplePay()['isActive'];
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
            getSupportedNetworks: function() {
                return CheckoutCom.getPaymentConfigApplePay()['supportedNetworks'];
            },

            /**
             * @returns {array}
             */
            getMerchantCapabilities: function() {
                return CheckoutCom.getPaymentConfigApplePay()['merchantCapabilities'];
            },

            /**
             * @returns {array}
             */
            getShippingMethods: function() {
                var shippingData = this.getSelectedShippingMethod();
                var shippingOptions = [{
                    label: shippingData.base.method_title,
                    amount: shippingData.selected.value,
                    detail: shippingData.base.carrier_title,
                    identifier: shippingData.base.method_code
                }]; 

                return shippingOptions;
            },

            /**
             * @returns {bool}
             */
            launchApplePay: function() {
                // Prepare the parameters
                var ap = CheckoutCom.getPaymentConfigApplePay();
                var debug = ap['debugMode'];
                var self = this;

                // Apply the button style
                $(self.button_target).addClass('apple-pay-button-' + ap['buttonStyle']);

                // Check if the session is available
                if (window.ApplePaySession) {
                    var merchantIdentifier = ap['merchantId'];
                    var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
                    promise.then(function (canMakePayments) {
                        if (canMakePayments) {
                            $(self.button_target).css('display', 'block');
                        } else {   
                            $('#got_notactive').css('display', 'block');
                        }
                    });
                } else {
                    $('#notgot').css('display', 'block');
                }

                // Handle the events
                $(self.button_target).click(function(evt) {
                    // Prepare the parameters
                    var runningTotal	= self.getQuoteValue();
                    var billingAddress  = self.getBillingAddress();
                    var shippingAddress = self.getShippingAddress();

                    // Build the payment request
                    var paymentRequest = {
                        currencyCode: CheckoutCom.getPaymentConfig()['quote_currency'],
                        countryCode: billingAddress.countryId,
                        lineItems: self.getLineItems(),
                        total: {
                           label: ap['storeName'],
                           amount: runningTotal
                        },
                        supportedNetworks: ['amex', 'masterCard', 'visa'], // todo - move to config
                        merchantCapabilities: ['supportsCredit', 'supportsDebit'] // todo - move to config
                    };

                    console.log(self.getSupportedNetworks());
                    console.log(self.getMerchantCapabilities());

                    // Start the payment session
                    var session = new ApplePaySession(1, paymentRequest);
                
                    // Merchant Validation
                    session.onvalidatemerchant = function (event) {
                        var promise = performValidation(event.validationURL);
                        promise.then(function (merchantSession) {
                            session.completeMerchantValidation(merchantSession);
                        }); 
                    }

                    // Merchant validation function
                    function performValidation(valURL) {
                        var controllerUrl = url.build('checkout_com/payment/applepayvalidation');
                        var validationUrl = controllerUrl + '?u=' + valURL;
                        
                        return new Promise(function(resolve, reject) {
                            var xhr = new XMLHttpRequest();
                            xhr.onload = function() {
                                var data = JSON.parse(this.responseText);
                                resolve(data);
                            };
                            xhr.onerror = reject;
                            xhr.open('GET', validationUrl);
                            xhr.send();
                        });
                    }

                    // Shipping contact
                    session.onshippingcontactselected = function(event) {                                                
                        var status = ApplePaySession.STATUS_SUCCESS;

                        // Shipping info
                        var shippingOptions = self.getShippingMethods();                   
                        
                        var newTotal = {
                            type: 'final',
                            label: ap['storeName'],
                            amount: runningTotal
                        };
                        
                        session.completeShippingContactSelection(status, shippingOptions, newTotal, self.getLineItems());
                    }

                    // Shipping method selection
                    session.onshippingmethodselected = function(event) {                                                
                        var status = ApplePaySession.STATUS_SUCCESS;
                        var newTotal = {
                            type: 'final',
                            label: ap['storeName'],
                            amount: runningTotal
                        };

                        session.completeShippingMethodSelection(status, newTotal, self.getLineItems());
                    }

                    // Payment method selection
                    session.onpaymentmethodselected = function(event) {
                        var newTotal = {
                            type: 'final',
                            label: ap['storeName'],
                            amount: runningTotal
                        };
                        
                        session.completePaymentMethodSelection( newTotal, self.getLineItems());
                    }

                    // Payment method authorization
                    session.onpaymentauthorized = function (event) {
                        var promise = sendPaymentToken(event.payment.token);
                        promise.then(function (success) {	
                            var status;
                            if (success){
                                status = ApplePaySession.STATUS_SUCCESS;
                                $(self.button_target).css('display', 'none');
                                $('#success').css('display', 'block');
                            } else {
                                status = ApplePaySession.STATUS_FAILURE;
                            }
                            
                            session.completePayment(status);
                        });
                    }

                    // Send payment token
                    function sendPaymentToken(paymentToken) {
                        return new Promise(function(resolve, reject) {
                            if (debug == true)
                            resolve(true);
                            else
                            reject;
                        });
                    }

                    // Session cancellation
                    session.oncancel = function(event) {
                        // Do something if needed
                    }

                    // Begin session
                    session.begin();
                });
            },
            
        });
    }
);