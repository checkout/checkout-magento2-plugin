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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define([
    'jquery',
    'CheckoutCom_Magento2/js/view/payment/utilities',
], function ($, Utilities) {
    'use strict';

    $.widget('checkoutCom.paypalReviewPlaceorder', {

        /**
         * @return {void}
         */
        _create: function () {
            this.editAddressForm = this.element.find('.form-address-edit');
            this.submitButton = this.element.find(this.options.buttonSelector);

            Utilities.loadCss('paypal', 'paypal');
            this._eventListeners();

            if (!this.editAddressForm.validation('isValid')) {
                this.submitButton.attr('disabled', 'true');
            }
        },

        /**
         * @return {void}
         */
        _eventListeners () {
            this.submitButton.on('click', (event) => {
                this.placeOrder(event);
            });
        },

        /**
         * @param {jQuery.Event} event
         * @return {void}
         */
        placeOrder: function (event) {
            $('body').trigger('processStart');

            if (this.options.chkPayPalContextId) {
                const submitButton = $(event.currentTarget);
                const data = {
                    methodId: this.options.methodId,
                    contextPaymentId: this.options.chkPayPalContextId,
                };

                // Place the order
                Utilities.placeOrder(
                    data,
                    this.options.methodId,
                    false
                )
                .then(() => {
                    $('body').trigger('processStop');
                });

                Utilities.cleanCustomerShippingAddress();
            } else {
                $('body').trigger('processStop');
            }
        },
    });

    return $.checkoutCom.paypalReviewPlaceorder;
});
