define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, t) {

        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true; // Fix billing address missing.
        const CODE = 'checkoutcom_googlepay';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + CODE + '.phtml'
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
                 * Methods
                 */

                /**
                 * @returns {string}
                 */
                getCode: function () {
                    return CODE;
                },

                /**
                 * @returns {string}
                 */
                getValue: function (field) {
                    return Utilities.getValue(CODE, field);
                },

                /**
                 * @returns {bool}
                 */
                isActive: function () {
                    return true;
                },

                /**
                 * Events
                 */

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
                }
            }
        );
    }
);
