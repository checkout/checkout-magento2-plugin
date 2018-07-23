/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/action/place-order',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function($, Component, Adapter, placeOrderAction, url, fullScreenLoader, additionalValidators) {
        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;

        return Component.extend({
            defaults: {
                active: true,
                template: Adapter.getName() + '/payment/default',
                code: Adapter.getCode(),
                targetForm: '#checkoutcom-magento2-form'
            },

            /**
             * @returns {exports}
             */
            initialize: function() {
                this._super();
            },

            initObservable: function() {
                this._super().observe([]);

                return this;
            },

            /**
             * @returns {string}
             */
            getCode: function() {
                return this.code;
            },

            /**
             * @returns {bool}
             */
            isActive: function() {
                return Adapter.getPaymentConfig()['isActive'];
            },

            /**
             * @returns {string}
             */
            getEmailAddress: function() {
                return Adapter.getEmailAddress();
            },

            /**
             * @returns {string}
             */
            getRedirectUrl: function() {
                return Adapter.getPaymentConfig()['redirect_url'];
            },

            /**
             * @returns {string}
             */
            getButtonLabel: function() {
                return Adapter.getPaymentConfig()['button_label'];
            },

            /**
             * @returns {string}
             */
            getInterfaceVersion: function() {
                return Adapter.getPaymentConfig()['interface_version'];
            },

            /**
             * @returns {string}
             */
            getRequestData: function() {
                return Adapter.getPaymentConfig()['request_data'];
            },

            /**
             * @returns {string}
             */
            getQuoteValue: function() {
                return Adapter.getQuoteValue();
            },

            /**
             * @returns {{method: (*|string|String), additional_data: {card_token_id: *}}}
             */
            getData: function() {
                return Adapter.getData();
            },

            /**
             * @returns {void}
             */
            saveSessionData: function(dataToSave) {
                Adapter.saveSessionData(dataToSave);
            },

            /**
             * @returns {string}
             */
            submitForm: function() {
                // Submit the form
                $(this.targetForm).submit();
            },

            getPlaceOrderDeferredObject: function() {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            /**
             * @returns {string}
             */
            beforePlaceOrder: function() {
                // Get self
                var self = this;

                // Validate before submission
                if (additionalValidators.validate()) {
                    // Payment action
                    if (Adapter.getPaymentConfig()['order_creation'] == 'before_auth') {
                        // Start the loader
                        fullScreenLoader.startLoader();

                        // Prepare the vars
                        var ajaxRequest;
                        var orderData = {
                            "agreement": [true]
                        };

                        // Avoid duplicate requests
                        if (ajaxRequest) {
                            ajaxRequest.abort();
                        }

                        // Send the request
                        ajaxRequest = $.ajax({
                            url: url.build(this.code + '/payment/placeOrderAjax'),
                            type: "post",
                            data: orderData
                        });

                        // Callback handler on success
                        ajaxRequest.done(function(response, textStatus, jqXHR) {
                            // Save order track id response object in session
                            self.saveSessionData({
                                customerEmail: self.getEmailAddress(),
                                orderTrackId: response.trackId
                            });

                            // Proceed with submission
                            fullScreenLoader.stopLoader();
                            self.submitForm();
                        });

                        // Callback handler on failure
                        ajaxRequest.fail(function(jqXHR, textStatus, errorThrown) {
                            // Todo - improve error handling
                        });

                        // Callback handler always
                        ajaxRequest.always(function() {
                            // Stop the loader
                            fullScreenLoader.stopLoader();
                        });
                    } else if (Adapter.getPaymentConfig()['order_creation'] == 'after_auth') {
                        // Save the session data
                        self.saveSessionData({
                            customerEmail: self.getEmailAddress()
                        });

                        // Proceed with submission
                        self.submitForm();
                    }
                }
            }
        });
    }

);