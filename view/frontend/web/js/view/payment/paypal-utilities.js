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
    'jquery',
    "CheckoutCom_Magento2/js/view/payment/utilities"
], function($, Utilities) {
    'use strict';

    return {

        /**
         * @public
         * @param {Object} config
         * @return {Promise}
         */
        paypalScriptLoader: function (config) {
            return new Promise((resolve, reject) => {
                const paypalScript = document.querySelector(`script[src*="${config.paypalScriptUrl}"]`);

                if (paypalScript) {
                    resolve();
                    return;
                }

                const script = document.createElement('script');

                script.addEventListener('load', () => {
                    resolve();
                });

                script.addEventListener('error', () => {
                    reject('Something wrong happened with paypal script load');
                });

                this.buildScript(script, config);
            });
        },

        /**
         * @public
         * @param {HTMLScriptElement} script
         * @param {Object} config
         */
        buildScript: function (script, config) {
            const scriptUrl = new URL(config.paypalScriptUrl);
            scriptUrl.searchParams.append('client-id', config.clientId);
            scriptUrl.searchParams.append('merchant-id', config.merchantId);
            scriptUrl.searchParams.append('intent', config.intent);
            scriptUrl.searchParams.append('commit', config.commit);
            scriptUrl.searchParams.append('currency', Utilities.getQuoteCurrency());
            scriptUrl.searchParams.append('disable-funding', 'credit,card,sepa');

            script.type = 'text/javascript';
            script.src = scriptUrl;
            script.dataset.pageType = config.pageType;
            script.dataset.partnerAttributionId = config.partnerAttributionId;

            document.head.appendChild(script);
        }
    };
});
