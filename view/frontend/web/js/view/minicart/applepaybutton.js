/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
], function(ko, Component, customerData) {
    'use strict';

    return Component.extend({
        isVisible: ko.observable(false),

        initialize: function() {
            this._super();
            let cartData = customerData.get('cart');
            this.isVisible(cartData()['summary_count'] > 0
                && cartData()['checkoutcom_apple_pay']
                && cartData()['checkoutcom_apple_pay']['active'] === '1',
            );

            cartData.subscribe((updatedCart) => {
                if (typeof window.checkoutConfig !== 'undefined'
                    && typeof window.checkoutConfig.quoteId === 'undefined') {
                    window.checkoutConfig = {...updatedCart['checkoutConfigProvider']};
                }

                this.isVisible(updatedCart['summary_count'] > 0
                    && updatedCart['checkoutcom_apple_pay']
                    && updatedCart['checkoutcom_apple_pay']['active'] === '1');
            });
        },
    });
});
