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
        const CODE = Utilities.getAlternativePaymentsCode();

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
                 * Events
                 */

                /**
                 * Content visible
                 *
                 * @return     {boolean}
                 */
                contentVisible: function() {

                    console.log('render alternative payments');

                    return true;

                },

                /**
                 * @returns {string}
                 */
                beforePlaceOrder: function () {
                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if (AdditionalValidators.validate()) {
                        // Submission logic

                    } else {
                        FullScreenLoader.stopLoader();
                    }
                },

                /**
                 * @returns {boolean}
                 */
                isPlaceOrderActionAllowed: function () {
                    return true;
                }
            }
        );
    }
);
