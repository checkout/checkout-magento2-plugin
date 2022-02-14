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

require([
    "jquery",
    "Magento_Checkout/js/view/payment/default",
    "CheckoutCom_Magento2/js/view/payment/utilities",
    "CheckoutCom_Magento2/js/view/payment/applepay-utilities",
    "Magento_Checkout/js/model/full-screen-loader",
    "Magento_Checkout/js/model/payment/additional-validators",
    "Magento_Checkout/js/action/redirect-on-success",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Customer/js/model/customer",
    "Magento_Customer/js/model/authentication-popup",
    'Magento_Checkout/js/model/quote',
    "mage/translate",
], function (
    $,
    Component,
    Utilities,
    ApplePayUtilities,
    FullScreenLoader,
    AdditionalValidators,
    RedirectOnSuccessAction,
    shippingService,
    Customer,
    AuthPopup,
    Quote,
    __
) {
    $(function () {
        let checkoutConfig = window.checkoutConfig.payment["checkoutcom_magento2"];
        const buttonTarget = "#ckoApplePayButton";
        const methodId = "checkoutcom_apple_pay";
        let selectedShippingMethod = null;
        let shippingMethodsAvailable = null;
        let shippingAddress = null;
        let totalsBreakdown = null;

        //  Check Apple Pay is enabled for the merchant
        if (typeof checkoutConfig["checkoutcom_apple_pay"] !== 'undefined') {
            // If Apple Pay is enabled on the cart inject the button
            if (checkoutConfig["checkoutcom_apple_pay"]["enabled_on_cart"] == 1) {
                Utilities.log("Apple Pay in Cart is enabled");

                // set the button theme and mode
                let button = document.querySelector("#ckoApplePayButton");
                button.style["-apple-pay-button-style"] = getButtonTheme();

                launchApplePay();
            }
        }
        /**
         * Initialize Apple Pay and handle session events
         *
         * @return {undefined}
         */
        function launchApplePay()
        {
            // Check if Apple Pay is available in the browser
            if (window.ApplePaySession) {
                var merchantIdentifier = getValue("merchant_id");
                var canMakePayments = window.ApplePaySession.canMakePayments(
                    merchantIdentifier
                );
                // If Apple Pay is possible for the merchant id, display the button
                if (canMakePayments) {
                    Utilities.log("Apple Pay can be used for the merchant ID provided");
                    $(buttonTarget).css("display", "inline-block");
                }
            } else {
                Utilities.log("Apple Pay can not be used for the merchant ID provided");
                $(buttonTarget).css("display", "none");
            }

            // Handle the Apple Pay button being pressed
            $(buttonTarget).click(function (evt) {
                // Build the payment request
                if (ApplePayUtilities.getIsVirtual()) {
                    // User must be signed in for virtual orders
                    if(!Customer.isLoggedIn()) {
                        AuthPopup.showModal();
                        return;
                    }

                    // Prepare the parameters
                    var runningTotal         = Utilities.getQuoteValue();

                    // Build the payment request
                    var paymentRequest = {
                        currencyCode: Utilities.getQuoteCurrency(),
                        countryCode: getCountryCode(),
                        total: {
                            label: window.location.host,
                            amount: runningTotal
                        },
                        supportedNetworks: getSupportedNetworks(),
                        merchantCapabilities: getMerchantCapabilities(),
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

                    // Start the payment session
                    Utilities.log(paymentRequest);
                    var session = new ApplePaySession(5, paymentRequest);
                } else {
                    var paymentRequest = {
                        currencyCode: Utilities.getQuoteCurrency(),
                        countryCode: getCountryCode(),
                        total: {
                            label: window.location.host,
                            amount: Utilities.getQuoteValue(),
                        },
                        supportedNetworks: getSupportedNetworks(),
                        merchantCapabilities: getMerchantCapabilities(),
                        requiredShippingContactFields: [
                            "postalAddress",
                            "name",
                            "phone",
                            "email",
                        ],
                        requiredBillingContactFields: [
                            "postalAddress",
                            "name",
                            "phone",
                            "email",
                        ],
                        shippingMethods: [],
                    };

                    // Start the payment session
                    Utilities.log(paymentRequest);
                    var session = new ApplePaySession(6, paymentRequest);
                }

                // Merchant Validation
                session.onvalidatemerchant = function (event) {
                    var promise = performValidation(event.validationURL);
                    promise
                        .then(function (merchantSession) {
                            Utilities.log("The Apple Pay session was generated");
                            session.completeMerchantValidation(merchantSession);
                        })
                        .catch(function (error) {
                            Utilities.log(error);
                        });
                };

                // When the shipping contact details are populated/selected
                session.onshippingcontactselected = function (event) {
                    shippingAddress = event.shippingContact;
                    // Get a list of available shipping methods for the shipping address of the customer
                    let shippingOptions = getShippingMethods(
                        shippingAddress.countryCode,
                        shippingAddress.postalCode
                    );

                    // Update the totals, so they reflect the all total items (shipping, tax...etc)
                    let totals = getCartTotals(shippingAddress);
                    totalsBreakdown = totals;

                    // Update the current totals breakdown
                    if (selectedShippingMethod) {
                        // Update the current totals breakdown
                        session.completeShippingContactSelection(
                            ApplePaySession.STATUS_SUCCESS,
                            shippingOptions,
                            totals.total,
                            totals.breakdown
                        );
                    } else {
                        session.completeShippingContactSelection({
                            status: "STATUS_FAILURE",
                            errors: [
                                new ApplePayError(
                                    "addressUnserviceable",
                                    "country",
                                    "No shipping methods available."
                                ),
                            ],
                            newTotal: totals.total,
                        });
                    }
                };

                // When the shipping method is populate/selected
                session.onshippingmethodselected = function (event) {
                    var status = ApplePaySession.STATUS_SUCCESS;

                    // Update the selected method
                    shippingMethodsAvailable.forEach(function (method) {
                        if (method.method_code == event.shippingMethod.identifier) {
                            selectedShippingMethod = method;
                        }
                    });
                    let totals = getCartTotals(shippingAddress);
                    totalsBreakdown = totals;

                    // Update the total to reflect the shipping method change
                    if (selectedShippingMethod) {
                        session.completeShippingMethodSelection(
                            status,
                            totals.total,
                            totals.breakdown
                        );
                    } else {
                        session.completeShippingMethodSelection(
                            ApplePaySession.STATUS_FAILURE
                        );
                    }
                };

                // When the payment method is populated/selected
                session.onpaymentmethodselected = function (event) {
                    if (ApplePayUtilities.getIsVirtual()) {
                        // Update the totals, so they reflect the all total items (shipping, tax...etc)
                        let totals = getVirtualCartTotals();
                        totalsBreakdown = totals;
                    }

                    session.completePaymentMethodSelection(
                        totalsBreakdown.total,
                        totalsBreakdown.breakdown
                    );
                };

                // When the payment is authorized via biometrics
                session.onpaymentauthorized = function (event) {
                    // Prepare the payload
                    var payload = {
                        methodId: methodId,
                        cardToken: event.payment.token,
                        source: methodId,
                    };

                    if (ApplePayUtilities.getIsVirtual()) {
                        setBilling(
                            event.payment.shippingContact,
                            event.payment.billingContact
                        );
                    } else {
                        setShippingAndBilling(
                            event.payment.shippingContact,
                            event.payment.billingContact
                        );
                    }

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

        /** Get the configured theme for the button
         * @return {string}
         */
        function getButtonTheme()
        {
            let theme = Utilities.getValue(methodId, "button_style");
            if (theme === "white-with-line") {
                return "white-outline"
            };
            return theme;
        }

        /**
         * Submit a payment for authorization
         *
         * @return {object}
         */
        function sendPaymentRequest(paymentData)
        {
            Utilities.log(paymentData);
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
         * Call the back end controller with the validation URL to generate an Apple Session
         *
         * @return {object}
         */
        function performValidation(valURL)
        {
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
         * Get the schemes enabled in the plugin settings
         *
         * @return {array}
         */
        function getSupportedNetworks()
        {
            let networksEnabled = getValue("supported_networks").split(",");
            return processSupportedNetworks(networksEnabled);
        }

        /**
         * Process supported network based on store country (SA)
         *
         * @return {array}
         */
        function processSupportedNetworks (networksEnabled) {
            if (networksEnabled.includes("mada") && !(Utilities.getStoreCountry === "SA")) {
                networksEnabled.splice(networksEnabled.indexOf("mada"), 1);
            }

            return networksEnabled;
        }

        /**
         * Get country code
         *
         * @return {string}
         */
        function getCountryCode()
        {
            return Utilities.getStoreCountry == "SA" ? "SA" : window.checkoutConfig.defaultCountryId;
        }

        /**
         * Get the value of a plugin setting based on the field name
         *
         * @return {string}
         */
        function getValue(field)
        {
            return Utilities.getValue(methodId, field);
        }

        /**
         * Get a list of available shipping methods based on the countryId and postCode
         *
         * @return {array}
         */
        function getShippingMethods(countryId, postCode)
        {
            let requestBody = {
                address: {
                    country_id: countryId.toUpperCase(),
                    postcode: postCode,
                },
            };

            shippingMethodsAvailable = ApplePayUtilities.getRestData(
                requestBody,
                "estimate-shipping-methods"
            );
            selectedShippingMethod = shippingMethodsAvailable[0];

            if (Quote.shippingMethod() && Quote.shippingMethod()['method_code']) {
                let index = 0;
                shippingMethodsAvailable.forEach(function (method, i) {
                    if (method.method_code == Quote.shippingMethod()['method_code']) {
                        selectedShippingMethod = method;
                        index = i;
                    }
                });
                if (index !== 0) {
                    shippingMethodsAvailable.splice(index, 1);
                    shippingMethodsAvailable.unshift(selectedShippingMethod);
                }
            }

            return formatShipping(shippingMethodsAvailable);
        }

        /**
         * Format the shipping methods from Magento format to Apple accepted format
         *
         * @return {array}
         */
        function formatShipping(shippingData)
        {
            let formatted = [];

            shippingData.forEach(function (shippingMethod) {
                if (shippingMethod.available) {
                    formatted.push({
                        label: shippingMethod.method_title,
                        amount: shippingMethod.price_incl_tax.toFixed(2),
                        identifier: shippingMethod.method_code,
                        detail: shippingMethod.carrier_title ? shippingMethod.carrier_title : '',
                    });
                }
            });
            return formatted;
        }

        /**
         * Return the cart totals (grand total, and breakdown)
         *
         * @return {object}
         */
        function getVirtualCartTotals() {
            let totalInfo = ApplePayUtilities.getRestData(null, "totals");
            let breakdown = [];

            totalInfo.total_segments.forEach(function (totalItem) {
                // ignore the grand total since it's handled separately
                if (totalItem.code === "grand_total") return;
                if (totalItem.value === null) return;
                // if there is not tax applied, remove it from the line items
                if (totalItem.code === "tax" && totalItem.value === 0) return;
                breakdown.push({
                    type: "final",
                    label: totalItem.title,
                    amount: totalItem.value,
                });
            });

            return {
                breakdown: breakdown,
                total: {
                    type: "final",
                    label: window.location.host,
                    amount: Utilities.getQuoteValue(),
                },
            };
        }

        /**
         * Return the cart totals (grand total, and breakdown)
         *
         * @return {object}
         */
        function getCartTotals(address) {
            let countryId = address.countryCode;
            let postCode = address.postalCode;

            let requestBody = {
                addressInformation: {
                    address: {
                        country_id: countryId.toUpperCase(),
                        postcode: postCode,
                        region_code: ApplePayUtilities.getAreaCode(postCode, countryId),
                    },
                    shipping_carrier_code: selectedShippingMethod ? selectedShippingMethod.carrier_code : "",
                    shipping_method_code: selectedShippingMethod ? selectedShippingMethod.method_code : "",
                },
            };

            let shippingInfo = ApplePayUtilities.getRestData(requestBody, "totals-information");

            let breakdown = [];

            let totalAmount = null;
            shippingInfo.total_segments.forEach(function (totalItem) {
                // ignore the grand total since it's handled separately
                if (totalItem.code === "grand_total") {
                    totalAmount = totalItem.value.toFixed(2)
                    return
                };
                // if there is not tax applied, remove it from the line items
                if (totalItem.code === "tax" && totalItem.value === 0) {
                    return
                };
                breakdown.push({
                    type: "final",
                    label: totalItem.title,
                    amount: totalItem.value.toFixed(2),
                });
            });

            return {
                breakdown: breakdown,
                total: {
                    type: "final",
                    label: window.location.host,
                    amount: totalAmount ? totalAmount : Utilities.getQuoteValue(),
                },
            };
        }
        /**
         * Update the cart to include updated shipping/billing methods
         *
         * @return {undefined}
         */
        function setShippingAndBilling(shippingDetails, billingDetails)
        {
            let requestBody = {
                addressInformation: {
                    shipping_address: {
                        country_id: shippingDetails.countryCode.toUpperCase(),
                        region_code: ApplePayUtilities.getAreaCode(shippingDetails.postalCode, shippingDetails.countryCode),
                        region_id: 0,
                        street: shippingDetails.addressLines,
                        postcode: shippingDetails.postalCode,
                        city: shippingDetails.locality,
                        firstname: shippingDetails.givenName,
                        lastname: shippingDetails.familyName,
                        email: shippingDetails.emailAddress,
                        telephone: shippingDetails.phoneNumber,
                    },
                    billing_address: {
                        country_id: billingDetails.countryCode.toUpperCase(),
                        street: billingDetails.addressLines,
                        postcode: billingDetails.postalCode,
                        city: billingDetails.locality,
                        firstname: billingDetails.givenName,
                        lastname: billingDetails.familyName,
                        email: shippingDetails.emailAddress,
                        telephone: shippingDetails.phoneNumber,
                    },
                    shipping_carrier_code: selectedShippingMethod.carrier_code,
                    shipping_method_code: selectedShippingMethod.method_code,
                },
            };
            ApplePayUtilities.getRestData(requestBody, "shipping-information");
        }

        function setBilling(shippingDetails, billingDetails) {
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

            ApplePayUtilities.getRestData(requestBody, "billing-address");
        }

        /**
         * @return {array}
         */
        function getMerchantCapabilities()
        {
            var output = ["supports3DS"];
            var capabilities = getValue("merchant_capabilities").split(",");

            return output.concat(capabilities);
        }
    });
});
