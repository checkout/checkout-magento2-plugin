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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define(
    'CheckoutCom_Magento2/js/flow/view/payment/method-renderer',
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
    ],
    function (
        Component,
        rendererList,
    ) {
        // Render flow payment method
        rendererList.push(
            {
                type: 'checkoutcom_flow',
                component: 'CheckoutCom_Magento2/js/flow/view/payment/checkoutcom_flow'
            },
            {
                type: 'checkoutcom_vault',
                component: 'CheckoutCom_Magento2/js/frames/view/payment/method-renderer/checkoutcom_vault'
            }
        );

        return Component.extend({});
    }
);
