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

define(
    [
        'jquery',
        'mage/translate'
    ],
    function ($, __) {
        'use strict';

        return {
            load: function (framesInstance, formId) {
                // Assign properties
                this.F = framesInstance;
                this.formId = formId;

                // Validation changed event
                this.F.addEventHandler(
                    this.F.Events.FRAME_VALIDATION_CHANGED,
                    this.onValidationChanged.bind(this)
                );

                return this.F;
            },

            getErrors: function () {
                var errors = {
                    'card-number': __('Please enter a valid card number'),
                    'expiry-date': __('Please enter a valid expiry date'),
                    'cvv': __('Please enter a valid CVV code')
                };

                return errors;
            },

            getErrorMessage: function (event) {
                if (event.isValid || event.isEmpty) {
                    return '';
                }

                return this.getErrors()[event.element];
            },

            onValidationChanged: function (event) {
                var targetSelector = '.error-message';
                var errorMessage = document.querySelector(targetSelector);
                errorMessage.textContent = this.getErrorMessage(event);
            }
        };
    }
);
