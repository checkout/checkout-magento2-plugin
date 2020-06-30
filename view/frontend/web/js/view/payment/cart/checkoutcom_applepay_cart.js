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
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'CheckoutCom_Magento2/js/view/payment/utilities',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/translate'
], function($, Component, Utilities, FullScreenLoader, AdditionalValidators, RedirectOnSuccessAction, __) {$(function() {
   let checkoutConfig = window.checkoutConfig.payment['checkoutcom_magento2'];
   const buttonTarget =  '#ckoApplePayButton';
    const methodId = 'checkoutcom_apple_pay';


      if(checkoutConfig['checkoutcom_apple_pay']['enabled_on_cart'] = 1) {
        launchApplePay()
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
        return getValue('supported_networks').split(',');
    }

    /**
     * @return {string}
     */
     function getValue(field) {
        return Utilities.getValue(methodId, field);
    }

    /**
     * @return {bool}
     */
    function launchApplePay() {
        // Prepare the parameters

        // Check if the session is available
        if (window.ApplePaySession) {
            var merchantIdentifier = getValue('merchant_id');
            console.log(merchantIdentifier);
            var canMakePayments = window.ApplePaySession.canMakePayments(merchantIdentifier);
            console.log(canMakePayments);

                    if (canMakePayments) {
                        $(buttonTarget).css('display', 'block');
                        $(buttonTarget).css('display', 'block');
                    } else {
                      console.log("apple pay couldn't load")
                    }
        } else {
            $(buttonTarget).css('display', 'none');
            Utilities.showMessage(
                'warning',
                __('Apple Pay is not available for this browser.'),
                methodId
            );
        }

        // Handle the events
        $(buttonTarget).click(
            function (evt) {
                    // Validate T&C submission
                    if (!AdditionalValidators.validate()) {
                        return;
                    }

                    // Prepare the parameters
                    var runningTotal         = Utilities.getQuoteValue();
                    var billingAddress       = Utilities.getBillingAddress();

                    // Build the payment request
                    var paymentRequest = {
                        currencyCode: Utilities.getQuoteCurrency(),
                        countryCode: window.checkoutConfig.defaultCountryId,
                        total: {
                            label: Utilities.getStoreName(),
                            amount: runningTotal
                        },
                        supportedNetworks: getSupportedNetworks(),
                        merchantCapabilities: getMerchantCapabilities()

                    };

                    // Start the payment session
                    var session = new ApplePaySession(1, paymentRequest);

                    // Merchant Validation
                    session.onvalidatemerchant = function (event) {
                        var promise = performValidation(event.validationURL);
                        promise.then(
                            function (merchantSession) {
                                session.completeMerchantValidation(merchantSession);
                            }
                        ).catch(
                            function (error) {
                                Utilities.log(error);
                            }
                        );
                    };

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

                        session.completeShippingContactSelection(status, shippingOptions, newTotal, getLineItems());
                    };

                    // Shipping method selection
                    session.onshippingmethodselected = function (event) {
                        var status = ApplePaySession.STATUS_SUCCESS;
                        var newTotal = {
                            type: 'final',
                            label: ap['storeName'],
                            amount: runningTotal
                        };

                        session.completeShippingMethodSelection(status, newTotal, getLineItems());
                    };

                    // Payment method selection
                    session.onpaymentmethodselected = function (event) {
                        var newTotal = {
                            type: 'final',
                            label: Utilities.getStoreName(),
                            amount: runningTotal
                        };

                        session.completePaymentMethodSelection(newTotal, getLineItems());
                    };

                    // Payment method authorization
                    session.onpaymentauthorized = function (event) {
                        // Prepare the payload
                        var payload = {
                            methodId: methodId,
                            cardToken: event.payment.token,
                            source: methodId
                        };

                        // Send the request
                        var promise = sendPaymentRequest(payload);
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
                    };

                    // Session cancellation
                    session.oncancel = function (event) {
                        Utilities.log(event);
                    };

                    // Begin session
                    session.begin();
            }
        );
    }


    /**
     * @return {object}
     */
        function sendPaymentRequest(paymentData) {
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
    }

    /**
     * @return {object}
     */
     function performValidation(valURL) {
        var controllerUrl = Utilities.getUrl('applepay/validation');
        var validationUrl = controllerUrl + '?u=' + valURL + '&method_id=' + methodId;

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
    }

    /**
     * @return {array}
     */
     function getMerchantCapabilities() {
        var output = ['supports3DS'];
        var capabilities = getValue('merchant_capabilities').split(',');

        return output.concat(capabilities);
    }
});});
