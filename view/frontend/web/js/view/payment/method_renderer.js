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
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        Adapter,
        RendererList
    ) {
        'use strict';

        var paymentMethod = window.checkoutConfig.payment;

console.log(1, paymentMethod);

        // Render the relevant payment methods
        RendererList.push(
            {
                type: 'checkoutcom_magento2_redirect_method',
                component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/redirect_method'
            }
        );

        // Render the relevant payment methods
        RendererList.push(
            {
                type: 'checkoutcom_alternative_payments',
                component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/checkoutcom_alternative_payments'
            }
        );

        return Component.extend({});
    }
);
