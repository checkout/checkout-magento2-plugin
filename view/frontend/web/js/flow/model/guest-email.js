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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

/**
 * Observable mirror of `quote.guestEmail`, which Magento keeps as a plain property.
 *
 * Fed by the email field mixin, read by the Flow loader. Kept free of any Flow dependency so the
 * mixin does not pull the Checkout.com SDK on pages where Flow is not used.
 */
define(['ko'], function (ko) {
    'use strict';

    return ko.observable('');
});
