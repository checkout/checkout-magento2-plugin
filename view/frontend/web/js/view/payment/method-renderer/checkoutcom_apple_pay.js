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
        "CheckoutCom_Magento2/js/view/payment/applepay-utilities",
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate'
    ],
    function (
        $,
        Component,
        Utilities,
        ApplePayUtilities,
        FullScreenLoader,
        AdditionalValidators,
        RedirectOnSuccessAction,
        __
    ) {
        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_apple_pay';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.html',
                    button_target: '#ckoApplePayButton',
                    redirectAfterPlaceOrder: false
                },

                /**
                 * @return {exports}
                 */
                initialize: function () {
                    this._super();
                    Utilities.setEmail();
                    Utilities.loadCss('apple-pay', 'apple-pay');

                    return this;
                },

                /**
                 * Methods
                 */

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
                 * Apple Pay
                 */

                /**
                 * @return {array}
                 */
                getLineItems: function () {
                    return [];
                },

                /**
                 * @return {array}
                 */
                getSupportedNetworks: function () {
                    let networksEnabled = this.getValue("supported_networks").split(",");
                    return this.processSupportedNetworks(networksEnabled);
                },

                /**
                 * Process supported network based on store country (SA)
                 *
                 * @return {array}
                 */
                processSupportedNetworks: function(networksEnabled) {
                    if (networksEnabled.includes("mada") && !(Utilities.getStoreCountry === "SA")) {
                        networksEnabled.splice(networksEnabled.indexOf("mada"), 1);
                    }

                    return networksEnabled;
                },

                /**
                 * Get Country code
                 *
                 * @return {string}
                 */
                getCountryCode: function() {
                    return  Utilities.getStoreCountry == "SA" ? "SA" : window.checkoutConfig.defaultCountryId;
                },

                /**
                 * @return {array}
                 */
                getMerchantCapabilities: function () {
                    var output = ['supports3DS'];
                    var capabilities = this.getValue('merchant_capabilities').split(',');

                    return output.concat(capabilities);
                },

                /**
                 * @return {object}
                 */
                performValidation: function (valURL) {
                    var controllerUrl = Utilities.getUrl('applepay/validation');
                    var validationUrl = controllerUrl + '?u=' + valURL + '&method_id=' + METHOD_ID;

                    return new Promise(
                        function (resolve, reject) {
                            var xhr = new XMLHttpRequest();
                            xhr.onload = function () {
                                Utilities.log(this.responseText);
                                var data = JSON.parse(this.responseText);
                                resolve(data);
                            };
                            xhr.onerror = reject;
                            xhr.open('GET', validationUrl);
                            xhr.send();
                        }
                    );
                },

                /**
                 * @return {object}
                 */
                sendPaymentRequest: function (paymentData) {
                    Utilities.log(paymentData);
                    return new Promise(
                        function (resolve, reject) {
                            $.ajax({
                                url: Utilities.getUrl('payment/placeorder'),
                                type: "POST",
                                data: paymentData,
                                success: function (data, textStatus, xhr) {
                                    if (data.success === true) {
                                        resolve(data.success);
                                    } else {
                                        reject;
                                    }
                                },
                                error: function (xhr, textStatus, error) {
                                    Utilities.log(error);
                                    reject;
                                }
                            });
                        }
                    );
                },

                setBilling: function(shippingDetails, billingDetails) {
                    let requestBody = {
                        address: {
                            country_id: billingDetails.countryCode.toUpperCase(),
                            region_code: ApplePayUtilities.getAreaCode(billingDetails.postalCode, billingDetails.countryCode),
                            region_id: 0,
                            street: billingDetails.addressLines,
                            postcode: billingDetails.postalCode,
                            city: billingDetails.locality,
                            firstname: billingDetails.givenName,
                            lastname: billingDetails.familyName,
                            email: shippingDetails.emailAddress,
                            telephone: shippingDetails.phoneNumber
                        }
                    };

                    Utilities.log(requestBody);
                    ApplePayUtilities.getRestData(requestBody, "billing-address");
                },

                /**
                 * @return {bool}
                 */
                launchApplePay: function () {
                    // Prepare the parameters
                    var self = this;

                    // Apply the button style
                    $(self.button_target)
                    .addClass('apple-pay-button-' + self.getValue('button_style'));

                    // Check if the session is available
                    if (window.ApplePaySession) {
                        var merchantIdentifier = self.getValue('merchant_id');
                        var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);
                        promise.then(
                            function (canMakePayments) {
                                if (canMakePayments) {
                                    $(self.button_target).css('display', 'block');
                                } else {
                                    Utilities.showMessage(
                                        'warning',
                                        __('Apple Pay is available but not currently active.'),
                                        METHOD_ID
                                    );
                                }
                            }
                        ).catch(
                            function (error) {
                                Utilities.log(error);
                            }
                        );
                    } else {
                        $(self.button_target).css('display', 'none');
                        Utilities.showMessage(
                            'warning',
                            __('Apple Pay is not available for this browser.'),
                            METHOD_ID
                        );
                    }

                    // Handle the events
                    $(self.button_target).click(
                        function (evt) {
                            if (Utilities.methodIsSelected(METHOD_ID)) {
                                // Validate T&C submission
                                if (!AdditionalValidators.validate()) {
                                    return;
                                }

                                // Prepare the parameters
                                var runningTotal         = Utilities.getQuoteValue();

                                // Build the payment request
                                if (ApplePayUtilities.getIsVirtual()) {
                                    var paymentRequest = {
                                        currencyCode: Utilities.getQuoteCurrency(),
                                        countryCode: self.getCountryCode(),
                                        total: {
                                            label: Utilities.getStoreName(),
                                            amount: runningTotal
                                        },
                                        supportedNetworks: self.getSupportedNetworks(),
                                        merchantCapabilities: self.getMerchantCapabilities(),
                                        requiredBillingContactFields: [
                                            "postalAddress",
                                            "name",
                                            "phone",
                                            "email"
                                        ],
                                        requiredShippingContactFields: [
                                            "phone",
                                            "email"
                                        ],
                                    };
                                } else {
                                    var paymentRequest = {
                                        currencyCode: Utilities.getQuoteCurrency(),
                                        countryCode: self.getCountryCode(),
                                        total: {
                                            label: Utilities.getStoreName(),
                                            amount: runningTotal
                                        },
                                        supportedNetworks: self.getSupportedNetworks(),
                                        merchantCapabilities: self.getMerchantCapabilities()
                                    };
                                }

                                // Start the payment session
                                Utilities.log(paymentRequest);
                                var session = new ApplePaySession(5, paymentRequest);

                                // Merchant Validation
                                session.onvalidatemerchant = function (event) {
                                    var promise = self.performValidation(event.validationURL);
                                    promise.then(
                                        function (merchantSession) {
                                            session.completeMerchantValidation(merchantSession);
                                        }
                                    ).catch(
                                        function (error) {
                                            Utilities.log(error);
                                        }
                                    );
                                }

                                // Shipping contact
                                session.onshippingcontactselected = function (event) {
                                    var status = ApplePaySession.STATUS_SUCCESS;

                                    // Shipping info
                                    var shippingOptions = [];

                                    var newTotal = {
                                        type: 'final',
                                        label: ap['storeName'],
                                        amount: runningTotal
                                    };

                                    session.completeShippingContactSelection(status, shippingOptions, newTotal, self.getLineItems());
                                }

                                // Shipping method selection
                                session.onshippingmethodselected = function (event) {
                                    var status = ApplePaySession.STATUS_SUCCESS;
                                    var newTotal = {
                                        type: 'final',
                                        label: ap['storeName'],
                                        amount: runningTotal
                                    };

                                    session.completeShippingMethodSelection(status, newTotal, self.getLineItems());
                                }

                                // Payment method selection
                                session.onpaymentmethodselected = function (event) {
                                    var newTotal = {
                                        type: 'final',
                                        label: Utilities.getStoreName(),
                                        amount: runningTotal
                                    };

                                    session.completePaymentMethodSelection(newTotal, self.getLineItems());
                                }

                                // Payment method authorization
                                session.onpaymentauthorized = function (event) {
                                    // Prepare the payload
                                    var payload = {
                                        methodId: METHOD_ID,
                                        cardToken: event.payment.token,
                                        source: METHOD_ID
                                    };

                                    if (ApplePayUtilities.getIsVirtual()) {
                                        self.setBilling(
                                            event.payment.shippingContact,
                                            event.payment.billingContact
                                        );
                                    }

                                    // Send the request
                                    var promise = self.sendPaymentRequest(payload);
                                    promise.then(
                                        function (success) {
                                            var status;
                                            if (success) {
                                                status = ApplePaySession.STATUS_SUCCESS;
                                            } else {
                                                status = ApplePaySession.STATUS_FAILURE;
                                            }

                                            session.completePayment(status);

                                            if (success) {
                                                // Redirect to success page
                                                FullScreenLoader.startLoader();
                                                RedirectOnSuccessAction.execute();
                                            }
                                        }
                                    ).catch(
                                        function (error) {
                                            Utilities.log(error);
                                        }
                                    );
                                }

                                // Session cancellation
                                session.oncancel = function (event) {
                                    Utilities.log(event);
                                }

                                // Begin session
                                session.begin();
                            }
                        }
                    );
                }
            }
        );
    }
);
