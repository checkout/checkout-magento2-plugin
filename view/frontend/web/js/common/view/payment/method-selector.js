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
    [
        'uiComponent',
        'CheckoutCom_Magento2/js/common/provider/general-settings'
    ],
    function (
        Component,
        GeneralSettings
    ) {
        if(GeneralSettings.useFrames()) {
            require(['CheckoutCom_Magento2/js/frames/view/payment/method-renderer'], function () {
            });
        }
        if(GeneralSettings.useFlow()) {
            require(['CheckoutCom_Magento2/js/flow/view/payment/method-renderer'], function () {
            });
        }

        return Component.extend({});
    }
);
