/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
define(
    [
        'jquery',
        'mage/validation'
    ],
    function ($) {
        'use strict';
        var checkoutConfig = window.checkoutConfig,
            agreementsConfig = checkoutConfig ? checkoutConfig.checkoutAgreements : {};

        var agreementsInputPath = '.payment-method._active div.checkout-agreements input';

        return {
            /**
             * Validate checkout agreements
             *
             * @returns {boolean}
             */
            validate: function() {

                if (!agreementsConfig.isEnabled) {
                    return true;
                }

                if ($(agreementsInputPath).length == 0) {
                    return true;
                }

                return $('#co-payment-form').validate({
                    errorClass: 'mage-error',
                    errorElement: 'div',
                    meta: 'validate',
                    errorPlacement: function (error, element) {
                        var errorPlacement = element;
                        if (element.is(':checkbox') || element.is(':radio')) {
                            errorPlacement = element.siblings('label').last();
                        }
                        errorPlacement.after(error);
                    }
                }).element(agreementsInputPath);
            }
        }
    }
);
