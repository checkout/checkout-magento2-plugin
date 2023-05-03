/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
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
