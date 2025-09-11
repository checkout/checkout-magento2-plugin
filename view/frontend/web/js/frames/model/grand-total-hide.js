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

define(function () {
    'use strict';

    var mixin = {
        isBaseGrandTotalDisplayNeeded: function () {
            let checkoutConfig = window.checkoutConfig.payment["checkoutcom_magento2"];
            return !parseInt(checkoutConfig["checkoutcom_configuration"]['active']);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
