define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'framesjs'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_card_payment';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
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
                isAvailable: function () {
                    return true;
                },

                /**
                 * @returns {boolean}
                 */
                isPlaceOrderActionAllowed: function () {
                    return true;
                },

                /**
                 * Events
                 */

                /**
                 * Content visible
                 *
                 * @return {void}
                 */
                getPaymentForm: function() {                    
                    var $btnSubmit = $('#ckoCardTargetButton'),
                        $frame = $('.frames-container'),
                        self =  this;

                    // Disable button
                    //Utilities.enableSubmit(METHOD_ID, false);

                    // Remove any existing event handlers
                    Frames.removeAllEventHandlers(Frames.Events.CARD_VALIDATION_CHANGED);
                    Frames.removeAllEventHandlers(Frames.Events.CARD_TOKENISED);
                    Frames.removeAllEventHandlers(Frames.Events.FRAME_ACTIVATED);

                    Frames.init({
                        publicKey: self.getValue('public_key'),
                        containerSelector: '.frames-container',
                        debugMode: self.getValue('debug'),
                        billingDetails: Utilities.getBillingAddress(),
                        customerName: Utilities.getCustomerName(),
                        //theme: self.getValue('theme'),
                        //themeOverride: self.getValue('themeOverride'),
                        //localisation: self.getValue('localisation'),
                        //localisation: 'EN-GB',
                        cardValidationChanged: function() {
                            //Utilities.enableSubmit(METHOD_ID, Frames.isCardValid());
                        }
                    });
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {
                    // Start the loader
                    FullScreenLoader.startLoader();
                    // Validate before submission
                    if (AdditionalValidators.validate()) {
                        Frames.submitCard();
                     //   return true;
                    } else {
                        this.handleFail({}); //@todo: imrpove needed
                        FullScreenLoader.stopLoader();
                    }

                    return false;
                },

                /**
                 * HTTP handlers
                 */

                /**
                 * @returns {string}
                 */
                request: function (res) {
                    Utilities.placeOrder({
                        type: 'token',
                        token: res.data.cardToken

                    },
                    this.handleSuccess,
                    this.handleFail);
                },

                handleSuccess: function(res) {
console.log(res);
                    FullScreenLoader.stopLoader();
                },

                handleFail: function(res) {
console.log(res);
                    Frames.unblockFields();
                    FullScreenLoader.stopLoader();
                }

            }
        );
    }
);
