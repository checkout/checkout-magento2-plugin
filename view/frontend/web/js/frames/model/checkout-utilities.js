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
    'jquery',
    'CheckoutCom_Magento2/js/common/view/payment/utilities',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/action/set-payment-information'
], function ($, Utilities, StepNavigator, setPaymentInformationAction) {
    'use strict';

    const PAYMENT_STEP_CODE = 'payment';

    return {

        /**
         * Workaround to refresh payment method information when guest customer
         * go back to shipping step & change his email address
         *
         * @param {UiClass} Component
         * @public
         */
        initSubscribers: function (Component) {
            const code = Component.getCode();

            StepNavigator.steps.subscribe((steps) => {
                if (this.getCurrentCheckoutStep(steps) === PAYMENT_STEP_CODE &&
                    Utilities.methodIsSelected(code)) {
                    setPaymentInformationAction(Component.messageContainer, {
                        method: code
                    });
                }
            });
        },

        /**
         * Return current checkout step code
         *
         * @param {Array} steps
         * @return string
         * @public
         */
        getCurrentCheckoutStep: function (steps) {
            return steps[StepNavigator.getActiveItemIndex()]['code'];
        }
    };
});
