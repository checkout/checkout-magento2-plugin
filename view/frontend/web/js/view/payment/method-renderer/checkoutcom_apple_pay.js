/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 7
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2019 Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function ($, Component, Adapter, FullScreenLoader, AdditionalValidators, t) {

        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true;
        var code = Adapter.getApplePayCode();
console.log(code);
        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + code + '.phtml',
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
                 * @returns {string}
                 */
                getCode: function () {
                    return code;
                },

                /**
                 * @returns {bool}
                 */
                isActive: function () {
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
                }
            }
        );
    }
);
