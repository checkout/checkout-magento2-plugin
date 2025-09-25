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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define([
    'underscore',
    'mage/utils/wrapper',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/action/select-payment-method'
],function (_, wrapper, checkoutData, paymentService, selectPaymentMethodAction) {
    'use strict';

    return function (checkoutDataResolver) {
        var check = window.checkoutConfig.payment['checkoutcom_magento2'];
        var ckoConfig = window.checkoutConfig.payment['checkoutcom_magento2'].checkoutcom_configuration

        /**
         * Auto select the last used payment method. If this is unavailable select the default.
         */
        var resolvePaymentMethod = wrapper.wrap(
            checkoutDataResolver.resolvePaymentMethod,
            function (originalResolvePaymentMethod) {
                var availablePaymentMethods = paymentService.getAvailablePaymentMethods();
                var method = this.getMethod(checkoutData.getSelectedPaymentMethod(), availablePaymentMethods);

                if ((!checkoutData.getSelectedPaymentMethod() && _.size(availablePaymentMethods) > 1) || _.isUndefined(method)) {
                    var method = this.getMethod(ckoConfig.default_method, availablePaymentMethods);

                    if (!_.isUndefined(method)) {
                        selectPaymentMethodAction(method);
                    } else {
                        var method = this.getMethod(check['checkoutcom_data']['user']['previous_method'], availablePaymentMethods);
                        if (!_.isUndefined(method)) {
                            selectPaymentMethodAction(method);
                        }
                    }
                }
                return originalResolvePaymentMethod();
            }
        )

        return _.extend(checkoutDataResolver, {
            resolvePaymentMethod: resolvePaymentMethod,

            /**
             * Get the payment method
             *
             * @param  {Array} availableMethods
             * @return {Object|undefined}
             */
            getMethod: function (method, availableMethods) {
                var autoselectMethod = method
                var matchedMethod;
                if (!_.isUndefined(autoselectMethod)) {
                    var matchedIndex = availableMethods.map(function(e) { return e.method; }).indexOf(autoselectMethod)

                    if (matchedIndex !== -1) {
                        matchedMethod = availableMethods[matchedIndex]
                    }
                }

                return matchedMethod;
            }
        });
    };
});
