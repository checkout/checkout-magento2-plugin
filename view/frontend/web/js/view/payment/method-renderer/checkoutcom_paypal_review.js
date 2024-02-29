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

define(
    [
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function($, Utilities, FullScreenLoader) {
        'use strict';

        $.widget('checkoutcom.paypalreviewplaceorder', {

            _create: function(config, element) {
                let self = this;

                $(this.options.buttonSelector).click(function() {
                    self.placeOrder();
                });
            },

            /**
             * @return {void}
             */
            placeOrder: function() {
                FullScreenLoader.startLoader();
                let self = this;

                if (this.options.chkPayPalContextId) {
                    $(this.options.buttonSelector).attr('disabled', 'disabled');
                    setTimeout(function(){
                        $(self.options.buttonSelector).removeAttr('disabled');
                    },1500);

                    let data = {
                        methodId: this.options.methodId,
                        contextPaymentId: this.options.chkPayPalContextId,
                    };

                    // Place the order
                    Utilities.placeOrder(
                        data,
                        this.options.methodId,
                        true
                    );
                    Utilities.cleanCustomerShippingAddress();

                    FullScreenLoader.stopLoader();
                }
            },
        });
        return $.checkoutcom.paypalreviewplaceorder;
    });
