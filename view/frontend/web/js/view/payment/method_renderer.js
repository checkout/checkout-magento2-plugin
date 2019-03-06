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

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'CheckoutCom_Magento2/js/view/payment/adapter',
    ],
    function (
        Component,
        rendererList,
        Adapter
    ) {

        'use strict';

        var paymentMethods = window.checkoutConfig.payment,
            methods = Adapter.getPaymentMethods();

        methods.forEach(function(element) {

            if(paymentMethods.hasOwnProperty(element) && +paymentMethods[element].enabled) {

                // Render the relevant payment methods
                rendererList.push(
                    {
                        type: element,
                        component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/' + element
                    }
                );

            }

        });

        return Component.extend({});

    }
);
