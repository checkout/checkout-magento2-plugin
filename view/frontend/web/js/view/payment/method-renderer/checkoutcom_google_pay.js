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
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate',
        'googlepayjs',
    ],
    function(
        $, Component, Utilities, FullScreenLoader, AdditionalValidators,
        RedirectOnSuccessAction, __) {
        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_google_pay';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID +
                        '.html',
                    button_target: '#ckoGooglePayButton',
                    redirectAfterPlaceOrder: false,
                },

                /**
                 * @return {exports}
                 */
                initialize: function() {
                    this._super();
                    Utilities.setEmail();
                    Utilities.loadCss('google-pay', 'google-pay');

                    return this;
                },

                /**
                 * Methods
                 */

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
                 * Google Pay
                 */
                /**
                 * @return {array}
                 */
                getAllowedNetworks: function() {
                    return this.getValue('allowed_card_networks').split(',');
                },

                /**
                 * @return {bool}
                 */
                launchGooglePay: function() {
                    // Prepare the parameters
                    var self = this;

                    // Apply the button style
                    $(self.button_target).
                        addClass('google-pay-button-' +
                            self.getValue('button_style'));

                    //  Button click event
                    $(self.button_target).click(
                        function(evt) {
                            if (Utilities.methodIsSelected(METHOD_ID)) {
                                // Validate T&C submission
                                if (!AdditionalValidators.validate()) {
                                    return;
                                }

                                // Prepare the payment parameters
                                var allowedPaymentMethods = [
                                    'CARD',
                                    'TOKENIZED_CARD'];
                                var allowedCardNetworks = self.getAllowedNetworks();

                                var tokenizationParameters = {
                                    tokenizationType: 'PAYMENT_GATEWAY',
                                    parameters: {
                                        'gateway': self.getValue(
                                            'gateway_name'),
                                        'gatewayMerchantId': self.getValue(
                                            'public_key'),
                                    },
                                };

                                // Prepare the Google Pay client
                                onGooglePayLoaded();

                                /**
                                 * Show Google Pay chooser when Google Pay purchase button is clicked
                                 */
                                var paymentDataRequest = getGooglePaymentDataConfiguration();
                                paymentDataRequest.transactionInfo = getGoogleTransactionInfo();

                                var paymentsClient = getGooglePaymentsClient();
                                paymentsClient.loadPaymentData(
                                    paymentDataRequest).then(
                                    function(paymentData) {
                                        // handle the response
                                        processPayment(paymentData);
                                    },
                                ).catch(
                                    function(error) {
                                        Utilities.log(error);
                                    },
                                );

                                /**
                                 * Initialize a Google Pay API client
                                 *
                                 * @return {google.payments.api.PaymentsClient} Google Pay API client
                                 */
                                function getGooglePaymentsClient() {
                                    return (new google.payments.api.PaymentsClient(
                                        {
                                            environment: self.getValue(
                                                'environment'),
                                        },
                                    ));
                                }

                                /**
                                 * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
                                 */
                                function onGooglePayLoaded() {
                                    var paymentsClient = getGooglePaymentsClient();
                                    paymentsClient.isReadyToPay(
                                        {allowedPaymentMethods: allowedPaymentMethods}).
                                        then(
                                            function(response) {
                                                if (response.result) {
                                                    prefetchGooglePaymentData();
                                                }
                                            },
                                        ).
                                        catch(
                                            function(err) {
                                                Utilities.log(err);
                                            },
                                        );
                                }

                                /**
                                 * Configure support for the Google Pay API
                                 *
                                 * @see     {@link https://developers.google.com/pay/api/web/reference/object#PaymentDataRequest|PaymentDataRequest}
                                 * @return {object} PaymentDataRequest fields
                                 */
                                function getGooglePaymentDataConfiguration() {
                                    return {
                                        merchantId: self.getValue(
                                            'merchant_id'),
                                        paymentMethodTokenizationParameters: tokenizationParameters,
                                        allowedPaymentMethods: allowedPaymentMethods,
                                        cardRequirements: {
                                            allowedCardNetworks: allowedCardNetworks,
                                        },
                                    };
                                }

                                /**
                                 * Provide Google Pay API with a payment amount, currency, and amount status
                                 *
                                 * @see     {@link https://developers.google.com/pay/api/web/reference/object#TransactionInfo|TransactionInfo}
                                 * @return {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
                                 */
                                function getGoogleTransactionInfo() {
                                    return {
                                        currencyCode: Utilities.getQuoteCurrency(),
                                        totalPriceStatus: 'FINAL',
                                        totalPrice: Utilities.getQuoteValue(),
                                    };
                                }

                                /**
                                 * Prefetch payment data to improve performance
                                 */
                                function prefetchGooglePaymentData() {
                                    var paymentDataRequest = getGooglePaymentDataConfiguration();

                                    // TransactionInfo must be set but does not affect cache
                                    paymentDataRequest.transactionInfo = {
                                        totalPriceStatus: 'NOT_CURRENTLY_KNOWN',
                                        currencyCode: Utilities.getQuoteCurrency(),
                                    };

                                    var paymentsClient = getGooglePaymentsClient();
                                    paymentsClient.prefetchPaymentData(
                                        paymentDataRequest);
                                }

                                /**
                                 * Process payment data returned by the Google Pay API
                                 *
                                 * @param {object} paymentData response from Google Pay API after shopper approves payment
                                 * @see   {@link https://developers.google.com/pay/api/web/reference/object#PaymentData|PaymentData object reference}
                                 */
                                function processPayment(paymentData) {
                                    // Start the loader
                                    FullScreenLoader.startLoader();

                                    // Prepare the payload
                                    var payload = {
                                        methodId: METHOD_ID,
                                        cardToken: {
                                            signature: JSON.parse(
                                                paymentData.paymentMethodToken.token).signature,
                                            protocolVersion: JSON.parse(
                                                paymentData.paymentMethodToken.token).protocolVersion,
                                            signedMessage: JSON.parse(
                                                paymentData.paymentMethodToken.token).signedMessage,
                                        },
                                        source: METHOD_ID,
                                    };

                                    // Send the request
                                    $.post(
                                        Utilities.getUrl('payment/placeorder'),
                                        payload,
                                        function(data, status) {
                                            if (data.success === true) {
                                                // Redirect to 3Ds page
                                                if (data.url) {
                                                    window.location.href = data.url;
                                                } else {
                                                    // Redirect to success page
                                                    RedirectOnSuccessAction.execute();
                                                }
                                            } else {
                                                FullScreenLoader.stopLoader();
                                                alert(
                                                    __('An error has occurred. Please try again.'));
                                            }
                                        },
                                    );
                                }
                            }
                        },
                    );
                },

                /**
                 * Events
                 */

                /**
                 * @return {string}
                 */
                beforePlaceOrder: function() {
                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if (AdditionalValidators.validate()) {
                        // Submission logic
                    } else {
                        FullScreenLoader.stopLoader();
                    }
                },
            },
        );
    },
);
