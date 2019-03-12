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

        window.checkoutConfig.reloadOnBillingAddress = true; // Fix billing address missing.
        const CODE = Utilities.getCardPaymentCode();

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + CODE
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                },

                initObservable: function () {
                    this._super().observe([]);
                    return this;
                },


                /**
                 * Getters and setters
                 */

                /**
                 * @returns {string}
                 */
                getCode: function () {
                    return CODE;
                },

                /**
                 * @returns {bool}
                 */
                isActive: function () {
                    return true;
                },

                /**
                 * @returns {boolean}
                 */
                isAvailable: function () {
                    return true;
                },

                /**
                 * Enables the submit button.
                 *
                 * @param      {boolean}   enabled  Status.
                 * @return     {void}
                 */
                enableSubmit: function (enabled) {

                    $('#' + this.getCode() + '_btn').prop('disabled', !enabled); //@todo: Add quote validation

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
                 * @return     {boolean}
                 */
                contentVisible: function() {

                    var $btnSubmit = $('#ckoCardTargetButton'),
                        $frame = $('.frames-container'),
                        self =  this;

                    Frames.init({
                        publicKey: Utilities.getField(CODE, 'public_key'),
                        containerSelector: '.frames-container',
                        debugMode: Utilities.getField(CODE, 'debug', false),

                        cardValidationChanged: function() {
                            self.enableSubmit(Frames.isCardValid());
                        },
                        cardTokenised: self.requestController,
                        cardTokenisationFailed: function(event) {
                            console.log('error', event); // @todo: handler error
                        }

                    });

                    return true;

                },

                /**
                 * @returns {void}
                 */
                placeOrder: function (event) {

                    // Start the loader
                    FullScreenLoader.startLoader();
                    Frames.submitCard(); //@note: it won't trigger a second time

                    // Validate before submission
                    if (AdditionalValidators.validate()) {
                        // Submission logic

                    } else {
                        FullScreenLoader.stopLoader();
                    }

                },

                /**
                 * @returns {string}
                 */
                requestController: function (event) {

                    var token = event.data.cardToken,
                        data = {};


                    $.post(Utilities.getEndPoint('placeorder'), data, function(res){
                        console.log('sucess', res);
                    }, 'json').done(function(res) {
                        console.log( "second success" , res);
                      })
                      .fail(function(res) {
                        console.log( "error", res );
                      })
                      .always(function(res) {
                        alert( "finished", res);
                      });

                    console.log('requestController');
                    FullScreenLoader.stopLoader();

                },


            }
        );
    }
);
