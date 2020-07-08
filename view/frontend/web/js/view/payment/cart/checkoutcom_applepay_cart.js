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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

require([
    "jquery",
    "Magento_Checkout/js/view/payment/default",
    "CheckoutCom_Magento2/js/view/payment/utilities",
    "Magento_Checkout/js/model/full-screen-loader",
    "Magento_Checkout/js/model/payment/additional-validators",
    "Magento_Checkout/js/action/redirect-on-success",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Customer/js/model/customer",
    "mage/translate",
], function (
    $,
    Component,
    Utilities,
    FullScreenLoader,
    AdditionalValidators,
    RedirectOnSuccessAction,
    shippingService,
    Customer,
    __
) {
    $(function () {
        let checkoutConfig =
            window.checkoutConfig.payment["checkoutcom_magento2"];
        const buttonTarget = "#ckoApplePayButton";
        const methodId = "checkoutcom_apple_pay";
        let shippingMethod = null;
        let shippingMethodsAvailable = null;

        if ((checkoutConfig["checkoutcom_apple_pay"]["enabled_on_cart"] = 1)) {
            launchApplePay();
        }

        /**
         * @return {bool}
         */
        function launchApplePay() {
            // Check if the session is available
            if (window.ApplePaySession) {
                var merchantIdentifier = getValue("merchant_id");
                var canMakePayments = window.ApplePaySession.canMakePayments(
                    merchantIdentifier
                );
                if (canMakePayments) {
                    $(buttonTarget).css("display", "inline-block");
                } else {
                    console.log("apple pay couldn't load");
                }
            } else {
                $(buttonTarget).css("display", "none");
                Utilities.showMessage(
                    "warning",
                    __("Apple Pay is not available for this browser."),
                    methodId
                );
            }

            // Handle the events
            $(buttonTarget).click(function (evt) {
                let event = shippingService.getShippingRates();
                // Prepare the parameters
                var runningTotal = Utilities.getQuoteValue();
                currentPrice = runningTotal;
                var billingAddress = Utilities.getBillingAddress();

                // Build the payment request
                var paymentRequest = {
                    currencyCode: Utilities.getQuoteCurrency(),
                    countryCode: window.checkoutConfig.defaultCountryId,
                    total: {
                        label: window.location.host,
                        amount: runningTotal,
                    },
                    supportedNetworks: getSupportedNetworks(),
                    merchantCapabilities: getMerchantCapabilities(),
                    requiredShippingContactFields: [
                        "postalAddress",
                        "name",
                        "phone",
                        "email"
                    ],
                    requiredBillingContactFields: [
                        "postalAddress",
                        "name",
                        "phone",
                        "email"
                    ],
                    shippingMethods: [],
                };

                // Start the payment session
                var session = new ApplePaySession(1, paymentRequest);

                // Merchant Validation
                session.onvalidatemerchant = function (event) {
                    var promise = performValidation(event.validationURL);
                    promise
                        .then(function (merchantSession) {
                            session.completeMerchantValidation(merchantSession);
                        })
                        .catch(function (error) {
                            Utilities.log(error);
                        });
                };

                // Shipping contact
                session.onshippingcontactselected = function (event) {
                    var status = ApplePaySession.STATUS_SUCCESS;

                    // Shipping info
                    var shippingAddress = event.shippingContact;
                    var shippingOptions = getShippingMethods(shippingAddress);

                    var newTotal = {
                        type: "final",
                        label: "implementation",
                        amount: runningTotal
                    };

                    session.completeShippingContactSelection(
                        0,
                        shippingOptions,
                        newTotal,
                        getLineItems()
                    );
                };

                // Shipping method selection
                session.onshippingmethodselected = function (event) {

                    var status = ApplePaySession.STATUS_SUCCESS;
                    runningTotal = (
                        Utilities.getQuoteValue() +
                        parseFloat(shippingMethod.amount)
                    ).toFixed(2);
                    var newTotal = {
                        type: "final",
                        label: "implementation",
                        amount: runningTotal,
                    };

                    session.completeShippingMethodSelection(
                        status,
                        newTotal,
                        getLineItems()
                    );
                };

                // Payment method selection
                session.onpaymentmethodselected = function (event) {
                    var newTotal = {
                        type: "final",
                        label: window.location.host,
                        amount: runningTotal
                    };

                    session.completePaymentMethodSelection(
                        newTotal,
                        getLineItems()
                    );
                };

                // Payment method authorization
                session.onpaymentauthorized = function (event) {
                    // Prepare the payload
                    var payload = {
                        methodId: methodId,
                        cardToken: event.payment.token,
                        source: methodId
                    };

                    setShippingAndBilling(event.payment);

                    // Send the request
                    var promise = sendPaymentRequest(payload);
                    promise
                        .then(function (success) {
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
                        })
                        .catch(function (error) {
                            Utilities.log(error);
                            status = ApplePaySession.STATUS_FAILURE;
                            session.completePayment(status);
                        });
                };

                // Session cancellation
                session.oncancel = function (event) {
                    Utilities.log(event);
                };

                // Begin session
                session.begin();
            });
        }

        /**
         * @return {object}
         */
        function sendPaymentRequest(paymentData) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: Utilities.getUrl("payment/placeorder"),
                    type: "POST",
                    data: paymentData,
                    success: function (data, textStatus, xhr) {
                        if (data.success === true) {
                            resolve(data.success);
                        } else {
                            reject();
                        }
                    },
                    error: function (xhr, textStatus, error) {
                        Utilities.log(error);
                        reject();
                    },
                });
            });
        }

        /**
         * @return {object}
         */
        function performValidation(valURL) {
            var controllerUrl = Utilities.getUrl("applepay/validation");
            var validationUrl =
                controllerUrl + "?u=" + valURL + "&method_id=" + methodId;

            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    Utilities.log(this.responseText);
                    var data = JSON.parse(this.responseText);
                    resolve(data);
                };
                xhr.onerror = reject;
                xhr.open("GET", validationUrl);
                xhr.send();
            });
        }

        /**
         * @return {array}
         */
        function getLineItems() {
            return [];
        }


        /**
         * @return {array}
         */
        function getSupportedNetworks() {
            return getValue("supported_networks").split(",");
        }

        /**
         * @return {string}
         */
        function getValue(field) {
            return Utilities.getValue(methodId, field);
        }
        
        function getShippingMethods(shippingAddress) {
            let requestBody = {
                address: {
                    country_id: shippingAddress.countryCode,
                    postcode: shippingAddress.postalCode,
                },
            };

            let restUrl = window.BASE_URL;
            restUrl += "rest/all/V1/guest-carts/"
                + window.checkoutConfig.quoteData.entity_id
                + "/estimate-shipping-methods"
                + "?form_key=" + window.checkoutConfig.formKey;

            if (Customer.isLoggedIn()) {
                restUrl = window.BASE_URL
                    + "rest/default/V1/carts/mine"
                    + "/estimate-shipping-methods"
                    + "?form_key=" + window.checkoutConfig.formKey;
            }

            // Send the AJAX request

            var result = null;

            $.ajax({
                url: restUrl,
                type: "POST",
                async: false,
                dataType: "json",
                contentType: "application/json",
                data: JSON.stringify(requestBody),
                success: function (data, status, xhr) {
                    result = formatShipping(data);
                    shippingMethodsAvailable = data;
                },
                error: function (request, status, error) {
                    Utilities.log(error);
                },
            });
            return result;
        }
        
        function getShippingCarrierCode(identifier) {
            shippingMethodsAvailable.foreach(function (method) {
                if(method.method_code == identifier) {
                    return method.carrier_code
                }
            })
        }

        function getShippingMethodCode(identifier) {
            shippingMethodsAvailable.foreach(function (method) {
                if(method.method_code == identifier) {
                    return method.method_code
                }
            })
        }


        function formatShipping(shippingData) {
            let formatted = [];

            shippingData.forEach(function (shippingMethod) {
                if (shippingMethod.available) {
                    formatted.push({
                        label: shippingMethod.method_title,
                        amount: shippingMethod.price_incl_tax,
                        identifier: shippingMethod.method_code,
                        detail: shippingMethod.carrier_title
                    });
                }
            });
            return formatted;
        }

        function setShippingAndBilling(paymentData) {
            let shippingDetails = paymentData.shippingContact;
            let billingDetails = paymentData.billingContact;

            let requestBody = {
                addressInformation: {
                    shipping_address: {
                        country_id: shippingDetails.countryCode.toUpperCase(),
                        street: shippingDetails.addressLines,
                        postcode: shippingDetails.postalCode,
                        city: shippingDetails.locality,
                        firstname: shippingDetails.givenName,
                        lastname: shippingDetails.familyName,
                        email: shippingDetails.emailAddress,
                        telephone: shippingDetails.phoneNumber
                    },
                    billing_address: {
                        country_id: billingDetails.countryCode.toUpperCase(),
                        street: billingDetails.addressLines,
                        postcode: billingDetails.postalCode,
                        city: billingDetails.locality,
                        firstname: billingDetails.givenName,
                        lastname: billingDetails.familyName,
                        email: shippingDetails.emailAddress,
                        telephone: shippingDetails.phoneNumber
                    },
                    shipping_carrier_code: getShippingCarrierCode(),
                    shipping_method_code: getShippingMethodCode()
                },
            };

            let restUrl = window.BASE_URL;
            restUrl += "rest/all/V1/guest-carts/"
                + window.checkoutConfig.quoteData.entity_id
                + "/shipping-information"
                + "?form_key=" + window.checkoutConfig.formKey;

            if (Customer.isLoggedIn()) {
                restUrl = window.BASE_URL
                    + "rest/default/V1/"
                    + "carts/mine/shipping-information"
                    + "?form_key=" + window.checkoutConfig.formKey;
            }

            let result = null;
            $.ajax({
                url: restUrl,
                type: "POST",
                async: false,
                dataType: "json",
                contentType: "application/json",
                data: JSON.stringify(requestBody),
                success: function (data, status, xhr) {
                    result = data;
                    console.log("setShippingAndBilling AJAX RESPONSE ", data);
                },
                error: function (request, status, error) {
                    console.log("setShippingAndBilling AJAX ERROR ", error);
                },
            });
        }

        /**
         * @return {array}
         */
        function getMerchantCapabilities() {
            var output = ["supports3DS"];
            var capabilities = getValue("merchant_capabilities").split(",");

            return output.concat(capabilities);
        }
    });
});
