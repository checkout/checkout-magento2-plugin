define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'framesjs'
    ],
    function ($, Component, Utilities, AdditionalValidators) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_card_payment';

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
             * @returns {boolean}
             */
            cleanEvents: function () {
                Frames.removeAllEventHandlers(Frames.Events.CARD_VALIDATION_CHANGED);
                Frames.removeAllEventHandlers(Frames.Events.CARD_TOKENISED);
                Frames.removeAllEventHandlers(Frames.Events.FRAME_ACTIVATED);
            },

            /**
             * Events
             */

            /**
             * Gets the payment form
             *
             * @return {void}
             */
            getPaymentForm: function() {                    
                var self = this;

                // Remove any existing event handlers
                this.cleanEvents();

                // Initialise the payment form
                Frames.init({
                    publicKey: self.getValue('public_key'),
                    containerSelector: '.frames-container',
                    debugMode: self.getValue('debug'),
                    //localisation: self.getValue('localisation'),
                    //localisation: 'EN-GB',
                    cardValidationChanged: function() {
                        if (Frames.isCardValid() && Utilities.getBillingAddress() != null) {
                            Utilities.allowPlaceOrder(self.buttonId, true);
                            Frames.submitCard();
                            Frames.unblockFields();
                        }
                        else {
                            Utilities.allowPlaceOrder(self.buttonId, false);
                        }
                    },
                    cardTokenised: function(event) {
                        // Store the card token for later submission
                        self.cardToken = event.data.cardToken;

                        // Add the card token to the form
                        Frames.addCardToken(
                            document.getElementById(self.formId),
                            event.data.cardToken
                        );
                    }
                });
            },

            /**
             * @returns {void}
             */
            placeOrder: function () {
                var self = this;
                if (AdditionalValidators.validate() && Frames.isCardValid()) {
                    // Place the order
                    Utilities.placeOrder({
                        methodId: METHOD_ID,
                        cardToken: self.cardToken
                    });

                    // Make sure the card form stays unblocked
                    Frames.unblockFields();
                }
            }
        });
    }
);
