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
    "Magento_Customer/js/model/authentication-popup",
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
    AuthPopup,
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

        Utilities.log("Apple Pay javascript loaded");

        //  Check Apple Pay is enabled for the merchant
        if (typeof checkoutConfig["checkoutcom_apple_pay"] !== 'undefined') {
            // If Apple Pay is enabled on the cart inject the button
            if (checkoutConfig["checkoutcom_apple_pay"]["enabled_on_cart"] == 1) {
                Utilities.log("Apple Pay is enabled in the plugin");

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
        function launchApplePay() {
            // Check if Apple Pay is available in the browser
            if (window.ApplePaySession) {
                var merchantIdentifier = getValue("merchant_id");
                var canMakePayments = window.ApplePaySession.canMakePayments(
                    merchantIdentifier
                );
                // If Apple Pay is possible for the merchant id, display the button
                if (canMakePayments) {
                    Utilities.log("Apple Pay can be used for the merchant id provided");
                    $(buttonTarget).css("display", "inline-block");
                }
            } else {
                Utilities.log("Apple Pay can not be used for the merchant id provided");
                $(buttonTarget).css("display", "none");
            }

            // Handle the Apple Pay button being pressed
            $(buttonTarget).click(function (evt) {
                // Build the payment request
                if (Utilities.getIsVirtual()) {
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
                        countryCode: window.checkoutConfig.defaultCountryId,
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
                    var session = new ApplePaySession(1, paymentRequest);
                } else {
                    var paymentRequest = {
                        currencyCode: Utilities.getQuoteCurrency(),
                        countryCode: window.checkoutConfig.defaultCountryId,
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
                    if(selectedShippingMethod) {
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
                    if (Utilities.getIsVirtual()) {
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

                    if (Utilities.getIsVirtual()) {
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
        function getButtonTheme() {
            let theme = Utilities.getValue(methodId, "button_style");
            if (theme === "white-with-line") return "white-outline";
            return theme;
        }

        /**
         * Submit a payment for authorization
         *
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
         * Call the back end controller with the validation URL to generate an Apple Session
         *
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
         * Get the schemes enabled in the plugin settings
         *
         * @return {array}
         */
        function getSupportedNetworks() {
            return getValue("supported_networks").split(",");
        }

        /**
         * Get the value of a plugin setting based on the field name
         *
         * @return {string}
         */
        function getValue(field) {
            return Utilities.getValue(methodId, field);
        }

        /**
         * Get a list of available shipping methods based on the countryId and postCode
         *
         * @return {array}
         */
        function getShippingMethods(countryId, postCode) {
            let requestBody = {
                address: {
                    country_id: countryId.toUpperCase(),
                    postcode: postCode,
                },
            };

            shippingMethodsAvailable = getRestData(
                requestBody,
                "estimate-shipping-methods"
            );
            selectedShippingMethod = shippingMethodsAvailable[0];
            return formatShipping(shippingMethodsAvailable);
        }

        /**
         * Format the shipping methods from Magento format to Apple accepted format
         *
         * @return {array}
         */
        function formatShipping(shippingData) {
            let formatted = [];

            shippingData.forEach(function (shippingMethod) {
                if (shippingMethod.available) {
                    formatted.push({
                        label: shippingMethod.method_title,
                        amount: shippingMethod.price_incl_tax,
                        identifier: shippingMethod.method_code,
                        detail: shippingMethod.carrier_title,
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
            let totalInfo = getRestData(null, "totals");
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
                    amount: totalInfo.base_grand_total.toFixed(2),
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
                        region_code: getAreaCode(postCode, countryId),
                    },
                    shipping_carrier_code: selectedShippingMethod ? selectedShippingMethod.carrier_code : "",
                    shipping_method_code: selectedShippingMethod ? selectedShippingMethod.method_code : "",
                },
            };

            let shippingInfo = getRestData(requestBody, "totals-information");

            let breakdown = [];

            shippingInfo.total_segments.forEach(function (totalItem) {
                // ignore the grand total since it's handled separately
                if (totalItem.code === "grand_total") return;
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
                    amount: shippingInfo.base_grand_total.toFixed(2),
                },
            };
        }
        /**
         * Update the cart to include updated shipping/billing methods
         *
         * @return {undefined}
         */
        function setShippingAndBilling(shippingDetails, billingDetails) {
            let requestBody = {
                addressInformation: {
                    shipping_address: {
                        country_id: shippingDetails.countryCode.toUpperCase(),
                        region_code: getAreaCode(shippingDetails.postalCode, shippingDetails.countryCode),
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
            getRestData(requestBody, "shipping-information");
        }

        function setBilling(shippingDetails, billingDetails) {
            let requestBody = {
                address: {
                    country_id: billingDetails.countryCode.toUpperCase(),
                    region_code: getAreaCode(billingDetails.postalCode, billingDetails.countryCode),
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
            
            getRestData(requestBody, "billing-address");
        }
        
        function getRestData(requestBody, m2ApiEndpoint) {
            let restUrl =
                window.BASE_URL +
                "rest/all/V1/guest-carts/" +
                window.checkoutConfig.quoteData.entity_id +
                "/" +
                m2ApiEndpoint;
            "?form_key=" + window.checkoutConfig.formKey;

            if (Customer.isLoggedIn()) {
                restUrl =
                    window.BASE_URL +
                    "rest/default/V1/carts/mine/" +
                    m2ApiEndpoint +
                    "?form_key=" +
                    window.checkoutConfig.formKey;
            }

            let result = null;
            let postType = m2ApiEndpoint == 'totals' ? "GET" : "POST";

            $.ajax({
                url: restUrl,
                type: postType,
                async: false,
                dataType: "json",
                contentType: "application/json",
                data: JSON.stringify(requestBody),
                success: function (data, status, xhr) {
                    result = data;
                },
                error: function (request, status, error) {
                    Utilities.log(error);
                },
            });
            return result;
        }

        /**
         * @return {array}
         */
        function getMerchantCapabilities() {
            var output = ["supports3DS"];
            var capabilities = getValue("merchant_capabilities").split(",");

            return output.concat(capabilities);
        }

        function getAreaCode(zipCode, countryCode) {
            // Ensure we have exactly 5 characters to parse
            if (zipCode.length === 5 && countryCode.toLowerCase() === "us") {
                // Ensure we don't parse strings starting with 0 as octal values
                const thiszip = parseInt(zipCode, 10);

                let st = null;
                if (thiszip >= 35000 && thiszip <= 36999) {
                    st = "AL";
                } else if (thiszip >= 99500 && thiszip <= 99999) {
                    st = "AK";
                } else if (thiszip >= 85000 && thiszip <= 86999) {
                    st = "AZ";
                } else if (thiszip >= 71600 && thiszip <= 72999) {
                    st = "AR";
                } else if (thiszip >= 90000 && thiszip <= 96699) {
                    st = "CA";
                } else if (thiszip >= 80000 && thiszip <= 81999) {
                    st = "CO";
                } else if (thiszip >= 6000 && thiszip <= 6999) {
                    st = "CT";
                } else if (thiszip >= 19700 && thiszip <= 19999) {
                    st = "DE";
                } else if (thiszip >= 32000 && thiszip <= 34999) {
                    st = "FL";
                } else if (thiszip >= 30000 && thiszip <= 31999) {
                    st = "GA";
                } else if (thiszip >= 96700 && thiszip <= 96999) {
                    st = "HI";
                } else if (thiszip >= 83200 && thiszip <= 83999) {
                    st = "ID";
                } else if (thiszip >= 60000 && thiszip <= 62999) {
                    st = "IL";
                } else if (thiszip >= 46000 && thiszip <= 47999) {
                    st = "IN";
                } else if (thiszip >= 50000 && thiszip <= 52999) {
                    st = "IA";
                } else if (thiszip >= 66000 && thiszip <= 67999) {
                    st = "KS";
                } else if (thiszip >= 40000 && thiszip <= 42999) {
                    st = "KY";
                } else if (thiszip >= 70000 && thiszip <= 71599) {
                    st = "LA";
                } else if (thiszip >= 3900 && thiszip <= 4999) {
                    st = "ME";
                } else if (thiszip >= 20600 && thiszip <= 21999) {
                    st = "MD";
                } else if (thiszip >= 1000 && thiszip <= 2799) {
                    st = "MA";
                } else if (thiszip >= 48000 && thiszip <= 49999) {
                    st = "MI";
                } else if (thiszip >= 55000 && thiszip <= 56999) {
                    st = "MN";
                } else if (thiszip >= 38600 && thiszip <= 39999) {
                    st = "MS";
                } else if (thiszip >= 63000 && thiszip <= 65999) {
                    st = "MO";
                } else if (thiszip >= 59000 && thiszip <= 59999) {
                    st = "MT";
                } else if (thiszip >= 27000 && thiszip <= 28999) {
                    st = "NC";
                } else if (thiszip >= 58000 && thiszip <= 58999) {
                    st = "ND";
                } else if (thiszip >= 68000 && thiszip <= 69999) {
                    st = "NE";
                } else if (thiszip >= 88900 && thiszip <= 89999) {
                    st = "NV";
                } else if (thiszip >= 3000 && thiszip <= 3899) {
                    st = "NH";
                } else if (thiszip >= 7000 && thiszip <= 8999) {
                    st = "NJ";
                } else if (thiszip >= 87000 && thiszip <= 88499) {
                    st = "NM";
                } else if (thiszip >= 10000 && thiszip <= 14999) {
                    st = "NY";
                } else if (thiszip >= 43000 && thiszip <= 45999) {
                    st = "OH";
                } else if (thiszip >= 73000 && thiszip <= 74999) {
                    st = "OK";
                } else if (thiszip >= 97000 && thiszip <= 97999) {
                    st = "OR";
                } else if (thiszip >= 15000 && thiszip <= 19699) {
                    st = "PA";
                } else if (thiszip >= 300 && thiszip <= 999) {
                    st = "PR";
                } else if (thiszip >= 2800 && thiszip <= 2999) {
                    st = "RI";
                } else if (thiszip >= 29000 && thiszip <= 29999) {
                    st = "SC";
                } else if (thiszip >= 57000 && thiszip <= 57999) {
                    st = "SD";
                } else if (thiszip >= 37000 && thiszip <= 38599) {
                    st = "TN";
                } else if (
                    (thiszip >= 75000 && thiszip <= 79999) ||
                    (thiszip >= 88500 && thiszip <= 88599)
                ) {
                    st = "TX";
                } else if (thiszip >= 84000 && thiszip <= 84999) {
                    st = "UT";
                } else if (thiszip >= 5000 && thiszip <= 5999) {
                    st = "VT";
                } else if (thiszip >= 22000 && thiszip <= 24699) {
                    st = "VA";
                } else if (thiszip >= 20000 && thiszip <= 20599) {
                    st = "DC";
                } else if (thiszip >= 98000 && thiszip <= 99499) {
                    st = "WA";
                } else if (thiszip >= 24700 && thiszip <= 26999) {
                    st = "WV";
                } else if (thiszip >= 53000 && thiszip <= 54999) {
                    st = "WI";
                } else if (thiszip >= 82000 && thiszip <= 83199) {
                    st = "WY";
                }

                return st;
            } else {
                return "";
            }
        }
    });
});
