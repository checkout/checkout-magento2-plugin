define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'framesjs'
    ],
    function ($, Component, Utilities, AdditionalValidators, Customer, Quote, urlBuilder) {
        'use strict';
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
                    supportedCards: null,
                    redirectAfterPlaceOrder: false
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                    this.initAddressObserver();
                    Utilities.setEmail();

                    return this;
                },

                initAddressObserver: function () {
                    var self = this;
                    Quote.billingAddress.subscribe(function () {
                        if (AdditionalValidators.validate() && Frames.isCardValid()) {
                            Utilities.allowPlaceOrder(self.buttonId, true);
                        }
                    });
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
                shouldDisplayCardIcons: function () {
                    return this.getValue('display_card_icons');
                },

                /**
                 * @returns {array}
                 */
                getCardIcons: function () {
                    return Utilities.getSupportedCards();
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
                 * Gets the payment form styles
                 *
                 * @return {void}
                 */
                getFormStyles: function() {
                    var formStyles = this.getValue('payment_form_styles');

                    // Reject empty, null or undefined values
                    if (formStyles === undefined || formStyles == null || formStyles.length <= 0) {
                        return false;
                    }

                    // Check if the styles are valid
                    try {
                        var stylesObj = JSON.parse(formStyles);
                    } catch (e) {
                        return null;
                    }

                    return stylesObj;   
                },   

                /**
                 * Gets the payment form layout
                 *
                 * @return {void}
                 */
                getFormLayout: function() {
                    return this.getValue('payment_form_layout');
                },

                /**
                 * Gets the module images path
                 *
                 * @return {void}
                 */
                getImagesPath: function() {
                    return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path;
                },  

                /**
                 * Gets the payment form
                 *
                 * @return {void}
                 */
                getPaymentForm: function () {
                    var self = this;
                    var formStyles = self.getFormStyles();
                    var address = Utilities.getBillingAddress(),
                        line1 = address.street[0] !== undefined ? address.street[0] : '',
                        line2 = address.street[1] !== undefined ? address.street[1] : ''

                    // Initialize the payment form
                    Frames.init(
                        {
                            publicKey: self.getValue('public_key'),
                            debug: Boolean(self.getValue('debug') && self.getValue('console_logging')),
                            localization: self.getValue('language_fallback'),
                            style: (formStyles) ? formStyles : {}, 
                            cardholder: {
                                name: Utilities.getCustomerName(),
                                phone: address.telephone,
                                billingAddress: {
                                    addressLine1: line1,
                                    addressLine2: line2,
                                    postcode: address.postcode,
                                    city: address.city,
                                    state: address.region,
                                    country: address.countryId,
                                }
                            }
                        }
                    );

                    self.addFramesEvents();
                    // Initialize other events
                    this.initEvents();
                },

                /**
                 * Add events to Frames.
                 * @returns {void}
                 */
                addFramesEvents: function () {
                    var self = this;

                    // Card validation changed event
                    Frames.addEventHandler(
                      Frames.Events.CARD_VALIDATION_CHANGED,
                      function (event) {
                        var valid = Frames.isCardValid() && Utilities.getBillingAddress() != null;
                        if (valid) {
                            Frames.submitCard();
                        }
                        Utilities.allowPlaceOrder(self.buttonId, valid);
                      }
                    );

                    // Card tokenized event
                    Frames.addEventHandler(
                      Frames.Events.CARD_TOKENIZED,
                        function (event) {
                            // Store the card token and the card bin
                            self.cardToken = event.token;
                            self.cardBin =  event.bin;
                            // Add the card token to the form
                            Frames.addCardToken(
                                document.getElementById(self.formId),
                                event.token
                            );
                            Frames.enableSubmitForm();
                        }
                    );
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
                    }
                }
            }
        );
    }
);
