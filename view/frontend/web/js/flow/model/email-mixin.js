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
 * Publish every validated guest email, so the Flow session can be recreated when the shopper
 * types it after the session was created (virtual quote, one-page checkouts).
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'CheckoutCom_Magento2/js/flow/model/guest-email'
    ],
    function (Quote, GuestEmail) {
        'use strict';

        return function (Component) {
            return Component.extend({
                /**
                 * The core sets `quote.guestEmail` synchronously, and only when the email is valid.
                 */
                emailHasChanged: function () {
                    this._super();

                    GuestEmail(Quote.guestEmail || '');
                }
            });
        };
    }
);
