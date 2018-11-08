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
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function($, Component, CheckoutCom, quote, url, setPaymentInformationAction, fullScreenLoader, additionalValidators, checkoutData, redirectOnSuccessAction, customer) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: 'CheckoutCom_Magento2/payment/applepay',
                code: 'checkout_com_applepay',
                card_token_id: null,
                redirectAfterPlaceOrder: true
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
                return quote.getTotals();
            },

            /**
             * @returns {string}
             */
            getQuoteCurrency: function() {
                return CheckoutCom.getPaymentConfig()['quote_currency'];
            },

            /**
             * @returns {bool}
             */
            launchApplePay: function() {
                var ap = CheckoutCom.getPaymentConfigApplePay();
                var debug = ap['debugMode'];
                var self = this;

                // Check if the session is available
                if (window.ApplePaySession) {
                    var merchantIdentifier = ap['merchantId'];
                    var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
                    promise.then(function (canMakePayments) {
                        if (canMakePayments) {
                            document.getElementById("applePay").style.display = "block";
                        } else {   
                            document.getElementById("got_notactive").style.display = "block";
                        }
                    });
                } else {
                    document.getElementById("notgot").style.display = "block";
                }

                // Handle the events
                document.getElementById("applePay").onclick = function(evt) {
                    // Prepare the parameters
                    var runningAmount 	= 42; // todo - replace by dynamic value
                    var runningPP		= 0; // todo - replace by dynamic value
                    getShippingCosts('domestic_std', true);
                    var runningTotal	= function() { return runningAmount + runningPP; }
                    var shippingOption = "";
                    var subTotalDescr	= "Test Goodies"; // todo - replace by dynamic value

                    // Shipping options function
                    function getShippingOptions(shippingCountry) {
                        if ( shippingCountry.toUpperCase() == "GB" ) { // todo - replace by dynamic value
                            shippingOption = [{label: 'Standard Shipping', amount: getShippingCosts('domestic_std', true), detail: '3-5 days', identifier: 'domestic_std'},{label: 'Expedited Shipping', amount: getShippingCosts('domestic_exp', false), detail: '1-3 days', identifier: 'domestic_exp'}];
                        } else {
                            shippingOption = [{label: 'International Shipping', amount: getShippingCosts('international', true), detail: '5-10 days', identifier: 'international'}];
                        }
                    }

                    // Shipping costs function
                    function getShippingCosts(shippingIdentifier, updateRunningPP ){
                        var shippingCost = 0;
                        
                        switch(shippingIdentifier) {
                            case 'domestic_std':
                                shippingCost = 3;
                                break;
                            case 'domestic_exp':
                                shippingCost = 6;
                                break;
                            case 'international':
                                shippingCost = 9;
                                break;
                            default:
                                shippingCost = 11;
                        }
                        
                        if (updateRunningPP == true) {
                            runningPP = shippingCost;
                        }
                                                    
                        return shippingCost;
                    }

                    // Build the payment request
                    var paymentRequest = {
                        currencyCode: 'GBP', // todo - replace by dynamic value
                        countryCode: 'GB', // todo - replace by dynamic value
                        requiredShippingContactFields: ['postalAddress'],
                        //requiredShippingContactFields: ['postalAddress','email', 'name', 'phone'],
                        //requiredBillingContactFields: ['postalAddress','email', 'name', 'phone'],
                        lineItems: [{label: subTotalDescr, amount: runningAmount }, {label: 'P&P', amount: runningPP }],
                        total: {
                           label: 'My test shop', // todo - replace by dynamic value
                           amount: runningTotal()
                        },
                        supportedNetworks: ['amex', 'masterCard', 'visa' ], // todo - move to config
                        merchantCapabilities: [ 'supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit' ] // todo - move to config
                    };

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
                        return new Promise(function(resolve, reject) {
                            var xhr = new XMLHttpRequest();
                            xhr.onload = function() {
                                var data = JSON.parse(this.responseText);
                                logit(data);
                                resolve(data);
                            };
                            xhr.onerror = reject;
                            xhr.open('GET', 'apple_pay_comm.php?u=' + valURL);
                            xhr.send();
                        });
                    }

                    // Shipping contact
                    session.onshippingcontactselected = function(event) {                        
                        getShippingOptions( event.shippingContact.countryCode );
                        
                        var status = ApplePaySession.STATUS_SUCCESS;
                        var newShippingMethods = shippingOption;
                        var newTotal = {
                            type: 'final',
                            label: 'My test shop', // todo - replace by dynamic value
                            amount: runningTotal()
                        };
                        var newLineItems = [
                            {
                                type: 'final',
                                label: subTotalDescr,
                                amount: runningAmount 
                            },
                            {
                                type:'final',
                                label: 'P&P',
                                amount: runningPP
                            }
                        ];
                        
                        session.completeShippingContactSelection(status, newShippingMethods, newTotal, newLineItems );
                    }

                    // Shipping method selection
                    session.onshippingmethodselected = function(event) {                        
                        getShippingCosts( event.shippingMethod.identifier, true );
                        
                        var status = ApplePaySession.STATUS_SUCCESS;
                        var newTotal = {
                            type: 'final',
                            label: 'My test shop', // todo - replace by dynamic value
                            amount: runningTotal()
                        };
                        var newLineItems = [
                            {
                                type: 'final',
                                label: subTotalDescr,
                                amount: runningAmount
                            },
                            {
                                type: 'final',
                                label: 'P&P',
                                amount: runningPP 
                            }
                        ];
                        
                        session.completeShippingMethodSelection(status, newTotal, newLineItems );
                    }

                    // Payment method selection
                    session.onpaymentmethodselected = function(event) {
                        var newTotal = {
                            type: 'final',
                            label: 'My test shop', // todo - replace by dynamic value
                            amount: runningTotal()
                        };
                        var newLineItems = [
                            {
                                type: 'final',
                                label: subTotalDescr,
                                amount: runningAmount
                            },
                            {
                                type: 'final',
                                label: 'P&P',
                                amount: runningPP
                            }
                        ];
                        
                        session.completePaymentMethodSelection( newTotal, newLineItems );
                    }

                    // Payment method authorization
                    session.onpaymentauthorized = function (event) {
                        var promise = sendPaymentToken(event.payment.token);
                        promise.then(function (success) {	
                            var status;
                            if (success){
                                status = ApplePaySession.STATUS_SUCCESS;
                                document.getElementById("applePay").style.display = "none";
                                document.getElementById("success").style.display = "block";
                            } else {
                                status = ApplePaySession.STATUS_FAILURE;
                            }
                            
                            session.completePayment(status);
                        });
                    }

                    // Send payment token
                    function sendPaymentToken(paymentToken) {
                        return new Promise(function(resolve, reject) {
                            if ( debug == true )
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
                };
            },
            
        });
    }
);