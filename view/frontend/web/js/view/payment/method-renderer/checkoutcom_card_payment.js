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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'CheckoutCom_Magento2/js/frames/multi',
        'CheckoutCom_Magento2/js/frames/single',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'framesjs'
    ],
    function ($, Component, Utilities, FramesMulti, FramesSingle, AdditionalValidators, Customer, Quote) {
        'use strict';
        window.checkoutConfig.reloadOnBillingAddress = true;
        const METHOD_ID = 'checkoutcom_card_payment';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.html',
                    buttonId: METHOD_ID + '_btn',
                    formId: METHOD_ID + '_frm',
                    formClone: null,
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
                    Utilities.loadCss(this.getFormLayout(), 'frames');
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

                    // Save card event
                    $('input[name="saveCard"]').on(
                        'click',
                        function () {
                            self.saveCard = this.checked;
                        }
                    );

                    // Option click event
                    $('.payment-method input[type="radio"]').on('click', function () {
                        if ($(this).attr('id') == METHOD_ID) {
                            self.getPaymentForm();
                        } else {
                            self.removePaymentForm();
                        }
                    });
                },

                /**
                 * @returns {void}
                 */
                handleFormState: function () {
                    if (Utilities.methodIsSelected(METHOD_ID)) {
                        this.getPaymentForm();
                    }
                },

                /**
                 * Gets the payment form styles
                 *
                 * @return {void}
                 */
                getFormStyles: function () {
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
                getFormLayout: function () {
                    return this.getValue('payment_form_layout');
                },

                /**
                 * Gets the module images path
                 *
                 * @return {void}
                 */
                getImagesPath: function () {
                    return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path;
                },

                /**
                 * Gets the payment form
                 *
                 * @return {void}
                 */
                getPaymentForm: function () {
                    // Prepare the needed variables
                    var self = this;
                    var formStyles = self.getFormStyles();
                    var address = Utilities.getBillingAddress(),
                        line1 = address.street[0] !== undefined ? address.street[0] : '',
                        line2 = address.street[1] !== undefined ? address.street[1] : ''

                    // Restore any existing HTML
                    if (this.formClone) {
                        // Restore the clone HTML
                        $('#' + this.formId).html(this.formClone);

                        // Empty the clone cache
                        this.formClone = null;
                    }

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

                    // Load the Frames instance component
                    Frames = this.addFramesComponent(Frames);

                    // Add the Frames events
                    this.addFramesEvents();
                },

                /**
                 * Loads a Frames component.
                 * @returns {void}
                 */
                addFramesComponent: function (framesInstance) {
                    if (this.getFormLayout() == 'multi') {
                        Frames = FramesMulti.load(framesInstance, this.formId);
                    } else {
                        Frames = FramesSingle.load(framesInstance, this.formId);
                    }

                    return Frames;
                },

                /**
                 * Removes the payment form
                 *
                 * @return {void}
                 */
                removePaymentForm: function () {
                    // Remove the events
                    Frames.removeAllEventHandlers(Frames.Events.CARD_VALIDATION_CHANGED);
                    Frames.removeAllEventHandlers(Frames.Events.CARD_TOKENIZED);
                    Frames.removeAllEventHandlers(Frames.Events.FRAME_VALIDATION_CHANGED);
                    Frames.removeAllEventHandlers(Frames.Events.PAYMENT_METHOD_CHANGED);

                    // Remove the HTML
                    var container = $('#' + this.formId);
                    this.formClone = $('#' + this.formId).html();
                    container.empty();
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
                            Utilities.allowPlaceOrder(self.buttonId, false);
                        }
                    );

                    // Card tokenized event
                    Frames.addEventHandler(
                        Frames.Events.CARD_TOKENIZED,
                        function (event) {
                            // Store the card token and the card bin
                            self.cardToken = event.token;
                            self.cardBin =  event.bin;

                            // Enable the submit form
                            Frames.enableSubmitForm();

                            // Enable place order button only when tokenized.
                            Utilities.allowPlaceOrder(self.buttonId, true);
                        }
                    );
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {
                    if (Utilities.methodIsSelected(METHOD_ID)) {
                        // Validate the order placement
                        if (AdditionalValidators.validate() && Frames.isCardValid()) {
                            // Prepare the payload
                            var payload = {
                                methodId: METHOD_ID,
                                cardToken: this.cardToken,
                                cardBin: this.cardBin,
                                saveCard: this.saveCard,
                                source: METHOD_ID
                            };

                            // Place the order
                            Utilities.placeOrder(payload, METHOD_ID);
                        }
                    }
                }
            }
        );
    }
);
