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

                // Payment method changed event
                this.F.addEventHandler(
                    this.F.Events.PAYMENT_METHOD_CHANGED,
                    this.paymentMethodChanged.bind(this)
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
                var pm = event.paymentMethod;
                var targetSelector = '#' + this.formId + ' .icon-container.payment-method';
                let container = document.querySelector(targetSelector);

                if (event.isValid || event.isEmpty) {
                    if (e == 'card-number' && !event.isEmpty) {
                        this.showPaymentMethodIcon(container, pm);
                    }
                    this.setDefaultIcon(e);
                    this.clearErrorIcon(e);
                    this.clearErrorMessage(e);
                } else {
                    if (e == 'card-number') {
                        this.clearPaymentMethodIcon();
                    }
                    this.setDefaultErrorIcon(e);
                    this.setErrorIcon(e);
                    this.setErrorMessage(e);
                }
            },

            clearErrorMessage: function (el) {
                var targetSelector = '#' + this.formId + ' .error-message__' + el;
                var message = document.querySelector(targetSelector);
                message.textContent = '';
            },

            clearErrorIcon: function (el) {
                var logo = document.getElementById('icon-' + el + '-error');
                logo.style.removeProperty('display');
            },

            showPaymentMethodIcon: function (parent, pm) {
                if (parent) {
                    parent.classList.add('show');
                }
                var logo = document.getElementById('logo-payment-method');
                if (pm) {
                    var name = pm.toLowerCase();
                    logo.setAttribute('src', this.getImagesPath() + name + '.svg');
                    logo.setAttribute('alt', pm || __('Payment method'));
                }
                logo.style.removeProperty('display');
            },

            getImagesPath: function () {
                return window.checkoutConfig.payment.checkoutcom_magento2.checkoutcom_data.images_path
                + '/frames/multi/';
            },

            clearPaymentMethodIcon: function (parent) {
                if (parent) {
                    parent.classList.remove('show');
                }
                var logo = document.getElementById('logo-payment-method');
                logo.style.setProperty('display', 'none');
            },

            setErrorMessage: function (el) {
                var targetSelector = '#' + this.formId + ' .error-message__' + el;
                var message = document.querySelector(targetSelector);
                message.textContent = this.getErrors()[el];
            },

            setDefaultIcon: function (el) {
                var selector = 'icon-' + el;
                var logos = this.getLogos();
                var logo = document.getElementById(selector);
                logo.setAttribute('src', this.getImagesPath() + logos[el].src + '.svg');
                logo.setAttribute('alt', logos[el].alt);
            },

            setDefaultErrorIcon: function (el) {
                var selector = 'icon-' + el;
                var logos = this.getLogos();
                var logo = document.getElementById(selector);
                logo.setAttribute('src', this.getImagesPath() + logos[el].src + '-error.svg');
                logo.setAttribute('alt', logos[el].alt);
            },

            setErrorIcon: function (el) {
                var logo = document.getElementById('icon-' + el + '-error');
                logo.style.setProperty('display', 'block');
            },

            paymentMethodChanged: function (event) {
                var pm = event.paymentMethod;
                var targetSelector = '#' + this.formId + ' .icon-container.payment-method';
                let container = document.querySelector(targetSelector);
                if (!pm) {
                    this.clearPaymentMethodIcon(container);
                } else {
                    this.clearErrorIcon('card-number');
                    this.showPaymentMethodIcon(container, pm);
                }
            }
        };
    }
);
