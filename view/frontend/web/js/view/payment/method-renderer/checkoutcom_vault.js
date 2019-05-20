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
                containerId: 'vault-container',
                redirectAfterPlaceOrder: false
            },

            /**
             * @returns {exports}
             */
            initialize: function () {
                this._super();

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
             * @returns {string}
             */
            getPublicHash: function() {
                return $('#vault-container input[name="savedCard"]:checked').val();
            },

            /**
             * @returns {void}
             */
            initWidget: function () {
                // Prepare some variables
                var self = this;
                var container = $('#' + self.containerId);

                // Send the content AJAX request
                $.ajax({
                    type: "POST",
                    url: Utilities.getUrl('vault/display'),
                    showLoader: true, 
                    success: function(data) {
                        // Insert the HTML content
                        container.append(data.html).show();
                        self.initEvents();

                        // Stop the loader
                        container.trigger('hide.loader');
                    },
                    error: function (request, status, error) {
                        console.log(error);
                    }
                });
            },

            /**
             * @returns {void}
             */
            initEvents: function () {
                // Prepare some variables
                var self = this;
                var container = $('#' + self.containerId);

                // Allow order placement if a card is selected
                container.find('.cko-vault-card').on('click', function() {
                    if ($('.cko-vault-card:focus').length > 0) {
                        $('#checkoutcom_vault_btn').prop('disabled', false);
                    }
                });
            },

            /**
             * @returns {void}
             */
            placeOrder: function () {
                var self = this;
                if (AdditionalValidators.validate()) {
                    // Place the order
                    Utilities.placeOrder({
                        methodId: METHOD_ID,
                        publicHash: self.getPublicHash()
                    });
                }
            }
        });
    }
);
