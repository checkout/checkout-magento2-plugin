define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function ($, Component, Utilities, AdditionalValidators, FullScreenLoader) {

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
                Utilities.setEmail();

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
             * @returns {string}
             */
            getCvv: function() {
                return $('#vault-container input[name="savedCard"]:checked')
                .closest('.cko-vault-card')
                .find('.vault-cvv input').val();
            },

            /**
             * @returns {void}
             */
            initWidget: function () {
                // Prepare some variables
                var self = this;
                var container = $('#' + self.containerId);

                // Start the loader
                FullScreenLoader.startLoader();
                
                // Send the content AJAX request
                $.ajax({
                    type: "POST",
                    url: Utilities.getUrl('vault/display'),
                    success: function(data) {
                        // Insert the HTML content
                        container.append(data.html).show();
                        self.initEvents();

                        // Stop the loader
                        FullScreenLoader.stopLoader();
                    },
                    error: function (request, status, error) {
                        FullScreenLoader.stopLoader();
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
                var listIem = container.find('.cko-vault-card');

                // Disable place order on click outside       
                $(document).mouseup(function (e) {
                    if (!listIem.is(e.target) && listIem.has(e.target).length === 0) {
                        Utilities.allowPlaceOrder(self.buttonId, false);
                    }
                });

                // Allow order placement if a card is selected
                listIem.on('click', function() {
                    Utilities.allowPlaceOrder(self.buttonId, true);
                });
            },

            /**
             * @returns {void}
             */
            placeOrder: function () {
                var self = this;
                if (AdditionalValidators.validate()) {
                    // Prepare the payload
                    var payload = {
                        methodId: METHOD_ID,
                        publicHash: self.getPublicHash()
                    }

                    // Add the CVV if needed
                    if (self.getValue('require_cvv')) {
                        payload.cvv = self.getCvv();
                    }

                    // Place the order
                    Utilities.placeOrder(payload);
                }
            }
        });
    }
);
