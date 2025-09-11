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
    'CheckoutCom_Magento2/js/frames/view/payment/method-renderer',
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'CheckoutCom_Magento2/js/frames/view/payment/config-loader'
    ],
    function (
        Component,
        rendererList,
        Config
    ) {
        // Render the active payment methods
        for (var method in Config) {
            if (method === 'checkoutcom_apple_pay' && !window.ApplePaySession) {
                continue; // Skip render if Apple Pay is run in wrong browser
            } else {
                rendererList.push(
                    {
                        type: method,
                        component: 'CheckoutCom_Magento2/js/frames/view/payment/method-renderer/' + method
                    }
                );
            }
        }

        return Component.extend({});
    }
);
