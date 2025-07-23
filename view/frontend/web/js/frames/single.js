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

            getLogos: function () {
                var logos = {};

                logos['card-number'] = {
                    src: 'card',
                    alt: __('Card number logo')
                };

                logos['expiry-date'] = {
                    src: 'exp-date',
                    alt: __('Expiry date logo')
                };

                logos['cvv'] = {
                    src: 'cvv',
                    alt: __('CVV logo')
                };

                return logos;
            },

            getErrors: function () {
                var errors = {
                    'card-number': __('Please enter a valid card number'),
                    'expiry-date': __('Please enter a valid expiry date'),
                    'cvv': __('Please enter a valid CVV code')
                };

                return errors;
            },

            onValidationChanged: function (event) {
                var e = event.element;

                if (event.isValid || event.isEmpty) {
                    this.clearErrorMessage(e);
                } else {
                    this.setErrorMessage(e);
                }
            },

            clearErrorMessage: function (el) {
                var targetSelector = '#' + this.formId + ' .error-message__' + el;
                var message = document.querySelector(targetSelector);
                message.textContent = '';
            },

            setErrorMessage: function (el) {
                var targetSelector = '#' + this.formId + ' .error-message__' + el;
                var message = document.querySelector(targetSelector);
                message.textContent = this.getErrors()[el];
            },
        };
    }
);
