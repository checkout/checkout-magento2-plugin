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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/frames/view/payment/config-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'mage/url',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'mage/cookies'
    ],
    function ($, Config, Quote, CheckoutData, Url, RedirectOnSuccessAction, FullScreenLoader, Customer, __) {
        'use strict';

        const KEY_CONFIG = 'checkoutcom_configuration';
        const KEY_DATA = 'checkoutcom_data';

        return {

            /**
             * Gets a field value.
             *
             * @param  {string}  methodId The method id
             * @param  {string}  field    The field
             * @param  {bool}  strict     The strict value
             * @return {mixed}            The value
             */
            getValue: function (methodId, field, strict) {
                var val = null;
                strict = (strict === undefined ? false : strict);
                if (methodId && Config.hasOwnProperty(methodId) && Config[methodId].hasOwnProperty(field)) {
                    val = Config[methodId][field]
                } else if (Config.hasOwnProperty(KEY_CONFIG) && Config[KEY_CONFIG].hasOwnProperty(field) && !strict) {
                    val = Config[KEY_CONFIG][field];
                }

                return val;
            },

            /**
             * Load a CSS file
             *
             * @return {void}
             */
            loadCss: function (fileName, folderPath, isCommon = false) {
                // Prepare the folder path
                folderPath = (folderPath) ? '/' + folderPath : '';

                // Get the CSS config parameters
                const useMinCss = window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.use_minified_css;
                const ext = (useMinCss == '1') ? '.min.css' : '.css';

                // Build the payment form CSS path
                let cssPath = window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.css_path;

                if (isCommon) {
                    cssPath = window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.css_common_path;
                }
                cssPath += folderPath + '/' + fileName + ext;

                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.media = 'all';
                css.href = cssPath;

                document.head.appendChild(css);
            },

            /**
             * Load a remote JS file
             *
             * @return {void}
             */
            loadRemoteJs: function (jsUrl) {
                // Append the CSS file
                $('head').append('<script type="text/javascript" src="' + jsUrl + '"></script>');
            },

            /**
             * Get the store name.
             *
             * @return {string}  The store name.
             */
            getStoreName: function () {
                return Config[KEY_DATA].store.name;
            },

            /**
             * Get the store country.
             *
             * @return {string}  The store country.
             */
            getStoreCountry: function () {
                return Config[KEY_DATA].store.country;
            },

            /**
             * Get the quote value.
             *
             * @return {float}  The quote value.
             */
            getQuoteValue: function () {
                var data = this.getRestQuoteData('payment-information');
                var collectTotals = data.totals.total_segments;
                var amount = null;

                collectTotals.forEach(function (total) {
                    if (total.code === "grand_total") {
                        amount = parseFloat(total.value);
                    }
                });

                return amount.toFixed(2);
            },

            /**
             * Get the updated quote data from the core REST API.
             *
             * @return {object}  The quote data.
             */
            getRestQuoteData: function (endpoint) {
                endpoint = (endpoint === null ? '' : '/' + endpoint);

                // Prepare the required parameters
                var self = this;
                var result = null;
                var restUrl = window.BASE_URL;
                var store = window.checkoutConfig && window.checkoutConfig.storeCode ? window.checkoutConfig.storeCode : 'default';
                const customerIsLoggedIn = window.checkoutConfig && window.checkoutConfig.isCustomerLoggedIn ? true : false;

                // Build the rest URL
                if (customerIsLoggedIn) {
                    restUrl += 'rest/';
                    restUrl += store;
                    restUrl += '/V1/';
                    restUrl += 'carts/mine';
                    restUrl += endpoint;
                    restUrl += '?form_key=' + window.checkoutConfig.formKey;
                } else {
                    restUrl += 'rest/';
                    restUrl += store;
                    restUrl += '/V1/';
                    restUrl += 'guest-carts/';
                    restUrl += window.checkoutConfig.quoteData.entity_id;
                    restUrl += endpoint;
                    restUrl += '?form_key=' + window.checkoutConfig.formKey;
                }

                // Send the AJAX request
                $.ajax({
                    url: restUrl,
                    type: 'GET',
                    contentType: "application/json",
                    dataType: "json",
                    async: false,
                    showLoader: true,
                    success: function (data, status, xhr) {
                        result = data;
                    },
                    error: function (request, status, error) {
                        self.log(error);
                    }
                });

                return result;
            },

            /**
             * Get the quote currency.
             *
             * @return {string}  The quote currency.
             */
            getQuoteCurrency: function () {
                var data = this.getRestQuoteData('payment-information');
                return data.totals.quote_currency_code;
            },

            /**
             * Check if APM should be auto selected.
             *
             * @return {void}
             */
            checkLastPaymentMethod: function () {
                var userData = this.getValue('checkoutcom_data', 'user');

                if (userData['previous_method'] == 'checkoutcom_apm') {
                    // Select the previous apm if it's available
                    if ($('.cko-apm#' + userData['previous_source']).length) {
                        $('.cko-apm#' + userData['previous_source']).trigger('click');
                    }
                }
            },

            /**
             * Check if stored card should be auto selected.
             *
             * @return {void}
             */
            checkStoredCard: function () {
                var userData = this.getValue('checkoutcom_data', 'user');
                if (userData['previous_method'] == 'checkoutcom_vault'
                && $('input[name=\'publicHash\'][value=\''+userData['previous_source']+'\']').length) {
                    $('input[name=\'publicHash\'][value=\''+userData['previous_source']+'\']').trigger('click');
                }
            },

            /**
             * Get card form labels
             *
             * @return {string}
             */
            getCardLabels: function (KEY) {
                return {
                    cardNumberLabel: Config[KEY].card_number_label ?
                        Config[KEY].card_number_label : __('Card number'),
                    expiryDateLabel: Config[KEY].expiration_date_label ?
                        Config[KEY].expiration_date_label : __('Expiration Date'),
                    cvvLabel: Config[KEY].cvv_label ?
                        Config[KEY].cvv_label : __('Card Verification Number')
                }
            },

            /**
             * Get card form placeholders
             *
             * @return {string}
             */
            getCardPlaceholders: function (KEY) {
                return {
                    cardNumberPlaceholder: Config[KEY].card_number_placeholder ?
                        Config[KEY].card_number_placeholder : __('Card number'),
                    expiryMonthPlaceholder: Config[KEY].expiration_date_month_placeholder ?
                        Config[KEY].expiration_date_month_placeholder : __('MM'),
                    expiryYearPlaceholder: Config[KEY].expiration_date_year_placeholder ?
                        Config[KEY].expiration_date_year_placeholder : __('YY'),
                    cvvPlaceholder: Config[KEY].cvv_placeholder ?
                        Config[KEY].cvv_placeholder : __('CVV')
                }
            },

            /**
             * Get the supported cards.
             *
             * @return {array}
             */
            getSupportedCards: function () {
                return Config[KEY_DATA].cards;
            },

            /**
             * Builds the controller URL.
             *
             * @param  {string}  path  The path
             * @return {string}
             */
            getUrl: function (path) {
                return Url.build('checkout_com/' + path);
            },

            /**
             * Customer name.
             *
             * @param {object} billingAddress
             * @return {string}
             */
            getCustomerNameByBillingAddress: function (billingAddress) {
                var customerName = '';
                if (billingAddress && billingAddress.firstname && billingAddress.lastname) {
                        customerName += billingAddress.firstname;
                        customerName += ' ' + billingAddress.lastname;
                }

                return customerName;
            },

            /**
             * Billing address.
             *
             * @return {object}  The billing address.
             */
            getBillingAddress: function () {
                return this.getRestQuoteData('billing-address');
            },

            /**
             * @return {string}
             */
            getEmail: function () {
                var emailCookieName = this.getValue(null, 'email_cookie_name');
                return window.checkoutConfig.customerData.email
                || Quote.guestEmail
                || CheckoutData.getValidatedEmailValue()
                || $.cookie(emailCookieName);
            },

            /**
             * @return {void}
             */
            setEmail: function () {
                var userEmail = this.getEmail();
                var emailCookieName = this.getValue(null, 'email_cookie_name');
                $.cookie(emailCookieName, userEmail);

                // If no email found, observe the core email field
                if (!userEmail) {
                    $('#customer-email').off('change').on('change', function () {
                        userEmail = Quote.guestEmail || CheckoutData.getValidatedEmailValue();
                        $.cookie(emailCookieName, userEmail);
                    });
                }
            },

            /**
             * @return {object}
             */
            getPhone: function () {
                var billingAddress = this.getBillingAddress();

                return {
                    number: billingAddress.telephone
                };
            },

            /**
             * Handle error logging.
             */
            log: function (val) {
                if (this.getValue(null, 'debug')
                    && this.getValue(null, 'console_logging')
                ) {
                    console.log(val);
                }
            },

            /**
             * Show a message.
             */
            showMessage: function (type, message, methodId) {
                this.clearMessages(methodId);
                var messageContainer = this.getMethodContainer(methodId).find('.message-cko');
                messageContainer.addClass('message-' + type + ' ' + type);
                messageContainer.append('<div>' + __(message) + '</div>');
                messageContainer.show();
            },

            /**
             * Show a message in global payment list and remove it after some time.
             */
            showGlobalMessage: function (type, message) {
                let messageContainer = $('<div class="message message-cko"></div>');
                $('.payment-methods .step-title').after(messageContainer);

                messageContainer.addClass('message-' + type + ' ' + type);
                messageContainer.append('<div>' + __(message) + '</div>');
                messageContainer.show();

                setTimeout(() => {messageContainer.hide()}, 15000);
            },

            /**
             * Show debug message.
             */
            showDebugMessage: function (type, message, methodId) {
                var messageContainer = this.getMethodContainer(methodId).find('.debug-message');
                messageContainer.addClass('message-' + type + ' ' + type);
                messageContainer.append('<div><pre style="white-space:pre-wrap;word-break: break-word;">' + message + '</pre></div>');
                messageContainer.show();
            },

            /**
             * Clear all messages.
             */
            clearMessages: function (methodId) {
                var messageContainer = this.getMethodContainer(methodId).find('.message');
                messageContainer.removeClass('message-warning warning message-error error');
                messageContainer.hide();
                messageContainer.empty();
            },

            /**
             * Get a payment method container instance.
             */
            getMethodContainer: function (methodId) {
                return $('#' + methodId + '_container');
            },

            /**
             * Check if an URL is valid.
             */
            isUrl: function (str) {
                var pattern = /^(?:\w+:)?\/\/([^\s\.]+\.\S{2}|localhost[\:?\d]*)\S*$/;
                return pattern.test(str);
            },

            /**
             * Handle the place order button state.
             */
            allowPlaceOrder: function (buttonId, yesNo) {
                $('#' + buttonId).prop('disabled', !yesNo);
            },

            /**
             * Check if a payment option is active.
             */
            methodIsSelected: function (idSelector) {
                var id = idSelector.replace('#', '');
                var selected = CheckoutData.getSelectedPaymentMethod();
                return id === selected || selected === null;
            },

            /**
             * Place a new order.
             *
             * @return {JQuery.ajax}
             */
            placeOrder: function (payload, methodId, startLoader = true, has3DS = null) {
                let self = this;
                const isFlow = methodId === 'checkoutcom_flow';
                const orderUrl = isFlow ? 'payment/placefloworder' : 'payment/placeorder';

                if (startLoader) {
                    // Start the loader
                    FullScreenLoader.startLoader();
                }

                // Send the request
                return $.ajax({
                    type: 'POST',
                    url: self.getUrl(orderUrl),
                    data: payload,
                    success: function (data) {
                        if (!data.success) {
                            FullScreenLoader.stopLoader();
                            self.showMessage('error', data.message, methodId);
                            if (data.debugMessage) {
                                self.showDebugMessage('error', data.debugMessage, methodId);
                            }
                        } else if (data.success && data.url) {
                            // Handle 3DS basic redirection
                            window.location.href = data.url;
                        } else {
                            // Prevent redirection before flow 3DS
                            if (isFlow) {
                                return true;
                            }

                            // Normal redirection
                            RedirectOnSuccessAction.execute();
                        }
                    },
                    error: function (request, status, error) {
                        self.showMessage('error', error, methodId);
                        FullScreenLoader.stopLoader();
                    }
                });
            },

            /**
             * Clean Checkout data
             */
            cleanCustomerShippingAddress: function() {
                CheckoutData.setNewCustomerShippingAddress(null);
            },

            redirectFailedPayment: function (token) {
                let url = token ? Url.build(
                    'checkout_com/payment/fail?cko-session-id=' +
                    token) :
                    Url.build('checkout_com/payment/fail');

                window.location.href = url;
            }
        };
    }
);
