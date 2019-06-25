define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'googlepayjs'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, __) {

        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true; // Fix billing address missing.
        const METHOD_ID = 'checkoutcom_google_pay';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.html',
                    button_target: '#ckoGooglePayButton',
                    redirectAfterPlaceOrder: false
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                    Utilities.setEmail();

                    return this;
                },

                /**
                 * Methods
                 */

                /**
                 * @returns {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @returns {string}
                 */
                getValue: function (field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * Google Pay
                 */
                /**
                 * @returns {array}
                 */
                getAllowedNetworks: function () {
                    return this.getValue('allowed_card_networks').split(',');
                },

                /**
                 * @returns {bool}
                 */
                launchGooglePay: function () {
                    // Prepare the parameters
                    var self = this;

                    // Apply the button style
                    $(self.button_target).addClass('google-pay-button-' + self.getValue('button_style'));

                    //  Button click event
                    $(self.button_target).click(
                        function (evt) {
                            // Validate T&C submission
                            if (!AdditionalValidators.validate()) {
                                return;
                            }

                            // Prepare the payment parameters
                            var allowedPaymentMethods = ['CARD', 'TOKENIZED_CARD'];
                            var allowedCardNetworks = self.getAllowedNetworks();
                
                            var tokenizationParameters = {
                                tokenizationType: 'PAYMENT_GATEWAY',
                                parameters: {
                                    'gateway':  self.getValue('gateway_name'),
                                    'gatewayMerchantId': Utilities.getValue('public_key')
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
                            .then(
                                function (paymentData) {
                                    // handle the response
                                    processPayment(paymentData);
                                }
                            )
                            .catch(
                                function (error) {
                                    Utilities.log(error);
                                }
                            );

                            /**
                             * Initialize a Google Pay API client
                             *
                             * @returns {google.payments.api.PaymentsClient} Google Pay API client
                             */
                            function getGooglePaymentsClient()
                            {
                                return (new google.payments.api.PaymentsClient(
                                    {
                                        environment: self.getValue('environment')
                                    }
                                ));
                            }
                
                            /**
                             * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
                             */
                            function onGooglePayLoaded()
                            {
                                var paymentsClient = getGooglePaymentsClient();
                                paymentsClient.isReadyToPay({ allowedPaymentMethods: allowedPaymentMethods })
                                .then(
                                    function (response) {
                                        if (response.result) {
                                            prefetchGooglePaymentData();
                                        }
                                    }
                                )
                                .catch(
                                    function (err) {
                                        //self.logEvent(err);
                                    }
                                );
                            }
                
                            /**
                             * Configure support for the Google Pay API
                             *
                             * @see     {@link https://developers.google.com/pay/api/web/reference/object#PaymentDataRequest|PaymentDataRequest}
                             * @returns {object} PaymentDataRequest fields
                             */
                            function getGooglePaymentDataConfiguration()
                            {
                                return {
                                    // @todo a merchant ID is available for a production environment after approval by Google
                                    // @see {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/overview|Test and deploy}
                                    merchantId: self.getValue('merchant_id'),
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
                             * @see     {@link https://developers.google.com/pay/api/web/reference/object#TransactionInfo|TransactionInfo}
                             * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
                             */
                            function getGoogleTransactionInfo()
                            {
                                return {
                                    currencyCode: Utilities.getQuoteCurrency(),
                                    totalPriceStatus: 'FINAL',
                                    totalPrice: Utilities.getQuoteValue()
                                };
                            }
                
                            /**
                             * Prefetch payment data to improve performance
                             */
                            function prefetchGooglePaymentData()
                            {
                                var paymentDataRequest = getGooglePaymentDataConfiguration();

                                // transactionInfo must be set but does not affect cache
                                paymentDataRequest.transactionInfo = {
                                    totalPriceStatus: 'NOT_CURRENTLY_KNOWN',
                                    currencyCode: Utilities.getQuoteCurrency()
                                };

                                var paymentsClient = getGooglePaymentsClient();
                                paymentsClient.prefetchPaymentData(paymentDataRequest);
                            }
                
                            /**
                             * Process payment data returned by the Google Pay API
                             *
                             * @param {object} paymentData response from Google Pay API after shopper approves payment
                             * @see   {@link https://developers.google.com/pay/api/web/reference/object#PaymentData|PaymentData object reference}
                             */
                            function processPayment(paymentData)
                            {
                                //self.logEvent(JSON.parse(paymentData.paymentMethodToken.token));
                                $.post(
                                    Utilities.getUrl('payment/placeorder'),
                                    {
                                        signature: JSON.parse(paymentData.paymentMethodToken.token).signature,
                                        protocolVersion: JSON.parse(paymentData.paymentMethodToken.token).protocolVersion,
                                        signedMessage: JSON.parse(paymentData.paymentMethodToken.token).signedMessage,
                                    },
                                    function (data, status) {
                                        if (data.status === true) {
                                            // redirect to success page
                                            FullScreenLoader.startLoader();
                                            redirectOnSuccessAction.execute();
                                        } else {
                                            alert(__('An error has occurred. Please try again.'));
                                        }
                                    }
                                );
                            }
                        }
                    );
                },

                /**
                 * Events
                 */

                /**
                 * @returns {string}
                 */
                beforePlaceOrder: function () {
                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if (AdditionalValidators.validate()) {
                        // Submission logic
                    } else {
                        FullScreenLoader.stopLoader();
                    }
                }
            }
        );
    }
);
