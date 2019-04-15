define([
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/config-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'mage/url',
        'mage/cookies',
 
    ],
    function ($, Config, Quote, CheckoutData, Url) {
        'use strict';

        const KEY_CONFIG = 'checkoutcom_configuration';

console.log(Config);

        return {

            /**
             * Gets a field value.
             *
             * @param      {string}  methodId The method id
             * @param      {string}  field    The field
             * @return     {mixed}            The value
             */
            getValue: function(methodId, field) { 
                var val = null;
                if (Config.hasOwnProperty(methodId) && Config[methodId].hasOwnProperty(field)) {
                    val = Config[methodId][field]
                }
                else if (Config.hasOwnProperty(KEY_CONFIG) && Config[KEY_CONFIG].hasOwnProperty(field)) {
                    val = Config[KEY_CONFIG][field];
                }

                return val;
            },

            /**
             * Builds the controller URL.
             *
             * @param      {string}  path  The path
             * @return     {string}
             */
            getUrl: function(path) {
                return Url.build('checkout_com/' + path);
            },

            /**
             * Customer name.
             *
             * @param      {bool} return in object format.
             * @return     {mixed}  The billing address.
             */
            getCustomerName: function(obj = false) {
                var billingAddress = Quote.billingAddress(),
                    name = {
                        first_name: billingAddress.firstname,
                        last_name: billingAddress.lastname
                    };


                if(!obj) {
                    name = name.first_name + ' ' + name.last_name
                }

                return name;

            },


            /**
             * Billing address.
             *
             * @return     {object}  The billing address.
             */
            getBillingAddress: function() {

                var billingAddress = Quote.billingAddress();

                return {
                    addressLine1: billingAddress.street[0],
                    addressLine2: billingAddress.street[1],
                    addressLine3: billingAddress.street[2],
                    postcode: billingAddress.postcode,
                    country: billingAddress.countryId,
                    city: billingAddress.city
                };

            },

            /**
             * @returns {string}
             */
            getEmail: function () {
                return window.checkoutConfig.customerData.email || Quote.guestEmail || CheckoutData.getValidatedEmailValue();
            },

            /**
             * @returns {object}
             */
            getPhone: function () {

                var billingAddress = Quote.billingAddress();

                return {
                    number: billingAddress.telephone
                };

            },


            /**
             * Methods
             */

            /**
             * Enables the submit button.
             *
             * @param      {boolean}   enabled  Status.
             * @return     {void}
             */
            enableSubmit: function (code, enabled) {

                $('#' + code + '_btn').prop('disabled', !enabled); //@todo: Add quote validation

            },

            /**
             * HTTP handlers
             */

            /**
             * Place a new order.
             * @returns {string}
             */
            placeOrder: function (source, successCallback, failCallback) {

                var data = {
                                source: source,
                                billing_address: this.getBillingAddress(),
                                phone: this.getPhone(),
                                customer: {
                                    email: this.getEmail(),
                                    name: this.getCustomerName(false)
                                }
                            };

                $.ajax({
                    type: 'POST',
                    url: this.getEndPoint('placeorder'),
                    data: JSON.stringify(data),
                    success: successCallback,
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8'
                }).fail(failCallback);

            }
        };
    }
);
