define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
    ],
    function ($, Component, Utilities, AdditionalValidators) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_vault';

        return Component.extend({
            defaults: {
                template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.phtml',
                buttonId: METHOD_ID + '_btn',
                formId: METHOD_ID + '_frm',
                cardToken: null,
                redirectAfterPlaceOrder: false
            },

            /**
             * @returns {exports}
             */
            initialize: function () {
                this._super();
                this.isPlaceOrderActionAllowed(false);

                return this;
            },

            /**
             * Getters and setters
             */

            /**
             * @returns {string}
             */
            getCode: function () {
                return METHOD_ID;
            },

            /**
             * @returns {string}
             */
            getValue: function(field) {
                return Utilities.getValue(METHOD_ID, field);
            },

            /**
             * @returns {void}
             */
            initWidget: function () {
                $.ajax({
                    type: "POST",
                    url: Utilities.getUrl('vault/display'),
                    success: function(data) {
                        $('#vault-container')
                        .append(data.html)
                        .accordion({
                            heightStyle: 'content',
                            animate: {
                                duration: 200
                            }
                        })
                        .show();
                    },
                    error: function (request, status, error) {
                        console.log(error);
                    }
                });
            },
                
            /**
             * @returns {void}
             */
            placeOrder: function () {
                var self = this;
                if (AdditionalValidators.validate()) {

                }
            }
        });
    }
);
