define([
        'jquery',
        'uiComponent',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'mage/url',
        'mage/cookies',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, GlobalMessageList, Quote, CheckoutData, Url, FullScreenLoader) {
        'use strict';

        return {

            /**
             * Gets a field value.
             *
             * @param      {string}  code    The code
             * @param      {string}  field   The field
             * @return     {mixed}  The value
             */
            /*

            getValue: function(code, field) {
                var val = (window.checkoutConfig.payment.hasOwnProperty('code')
                && window.checkoutConfig.payment[code].hasOwnProperty('field'))
                ? window.checkoutConfig.payment[code][field]
                : null;

                return JSON.parse(val);
            },
            */

            /**
             * Gets the field.
             *
             * @param      {string}  controller  The controller
             * @return     {string}
             */
            getEndPoint: function(controller) {

                return Url.build('checkout_com/payment/' + controller);

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
             * DOM handlers
             */

             createInput: function (el) {

                var $div = $('<div>').attr({
                        class: 'input-group'
                    }),
                    $label = $('<label>').attr({
                        class: 'icon',
                        for: el.name
                    }),
                    $icon = $('<span>').attr({
                        class: 'ckojs ' + el.icon,
                    }),
                    $input = $('<input>').attr({
                        id: el.name,
                        type: el.type,
                        placeholder: el.placeholder,
                        name: el.name,
                        'aria-label': el.placeholder,
                        autocomplete: 'off',
                        class: 'input-control',
                        required: el.required,
                        pattern: el.pattern,
                        'data-validation': el.validation
                    });

                return $div.append($label.append($icon)).append($input);

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
