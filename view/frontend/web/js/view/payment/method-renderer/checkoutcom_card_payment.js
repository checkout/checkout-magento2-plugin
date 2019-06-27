define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'framesjs'
    ],
    function ($, Component, Utilities, AdditionalValidators, Customer) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_card_payment';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.html',
                    buttonId: METHOD_ID + '_btn',
                    formId: METHOD_ID + '_frm',
                    cardToken: null,
                    cardBin: null,
                    saveCard: false,
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
                 * @returns {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @returns {string}
                 */
                getValue: function (field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * @returns {string}
                 */
                isVaultEnabled: function () {
                    return this.getValue('active');
                },

                /**
                 * @returns {string}
                 */
                isSaveCardEnabled: function () {
                    return this.getValue('save_card_option');
                },

                /**
                 * @returns {bool}
                 */
                isLoggedIn: function () {
                    return Customer.isLoggedIn();
                },

                /**
                 * @returns {void}
                 */
                initEvents: function () {
                    var self = this;
                    $('input[name="saveCard"]').on(
                        'click',
                        function () {
                            self.saveCard = this.checked;
                        }
                    );
                },

                /**
                 * @returns {void}
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
                getPaymentForm: function () {
                    var self = this;

                    // Remove any existing event handlers
                    this.cleanEvents();

                    // Initialize the payment form
                    Frames.init(
                        {
                            publicKey: self.getValue('public_key'),
                            containerSelector: '.frames-container',
                            debugMode: (self.getValue('debug') && self.getValue('console_logging')),
                            localisation: self.getValue('language_fallback'),
                            theme: self.getValue('form_theme'),
                            customerName: Utilities.getCustomerName(),
                            cardValidationChanged: function () {
                                if (Frames.isCardValid() && Utilities.getBillingAddress() != null) {
                                    Utilities.allowPlaceOrder(self.buttonId, true);
                                    Frames.submitCard();
                                    Frames.unblockFields();
                                } else {
                                    Utilities.allowPlaceOrder(self.buttonId, false);
                                }
                            },
                            cardTokenised: function (event) {
                                // Store the card token and the card bin
                                self.cardToken = event.data.cardToken;
                                self.cardBin =  event.data.card.bin;

                                // Add the card token to the form
                                Frames.addCardToken(
                                    document.getElementById(self.formId),
                                    event.data.cardToken
                                );
                            }
                        }
                    );

                    // Initialize other events
                    self.initEvents();
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {
                    // Prepare some variables
                    var self = this;

                    // Validate the order placement
                    if (AdditionalValidators.validate() && Frames.isCardValid()) {
                        // Prepare the payload
                        var payload = {
                            methodId: METHOD_ID,
                            cardToken: self.cardToken,
                            cardBin: self.cardBin,
                            saveCard: self.saveCard,
                            source: METHOD_ID
                        };

                        // Place the order
                        Utilities.placeOrder(payload, METHOD_ID);

                        // Make sure the card form stays unblocked
                        Frames.unblockFields();
                    }
                }
            }
        );
    }
);
