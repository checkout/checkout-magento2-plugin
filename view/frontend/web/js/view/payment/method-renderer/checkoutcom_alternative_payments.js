define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators) {

        'use strict';

        window.checkoutConfig.reloadOnBillingAddress = true; // Fix billing address missing.
        const CODE = Utilities.getAlternativePaymentsCode();

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + CODE
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                },

                initObservable: function () {
                    this._super().observe([]);
                    return this;
                },


                /**
                 * Getters and setters
                 */

                /**
                 * @returns {string}
                 */
                getCode: function () {
                    return CODE;
                },

                /**
                 * @returns {bool}
                 */
                isActive: function () {
                    return true;
                },

                /**
                 * @returns {boolean}
                 */
                isAvailable: function () {
                    return true;
                },

                /**
                 * @returns {boolean}
                 */
                isPlaceOrderActionAllowed: function () {
                    return true;
                },

                /**
                 * @returns array
                 */
                getAlternativePaymentsList: function () {
                    var list = JSON.parse(Utilities.getValue(CODE, 'alternatives', '')),
                        self = this;

                    list.forEach(function(el) {

                        el['loader'] = self.missingLoader;
                        if(typeof self[el.id] == 'function') {
                            el['loader'] = self[el.id];
                        }

                    });

                    return list;
                },


                /**
                 * Events
                 */

                /**
                 * Render form.
                 *
                 * @return     {boolean}
                 */
                renderSubForm: function(data, events) {

                    var $radio = $(events.currentTarget).prev()
                        self = this;



                    if(!$radio.prop("checked")) {

                        // Tick the radio bottom
                        $radio.prop("checked", true);

                        // Destroy other forms
                        $('.cko-alternative-form').empty();

                        // Create inputs
                        var $form = $('#cko-alternative-form-' + data.id);
                        data.loader($form);

                    }

                },


                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                alipay: function($form) {},

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                boleto: function($form) {

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'cpf',
                            type: 'tel',
                            name: 'cpf',
                            required: this.fields.includes('cpf'),
                            pattern: '.{11,11}'
                         }));

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-calendar',
                            placeholder: 'birthdate',
                            type: 'text',
                            name: 'birthDate',
                            required: this.fields.includes('birthDate'),
                            pattern: '\\d{1,2}/\\d{1,2}/\\d{4}'
                         }));

                },

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                giropay: function($form) {

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'bic',
                            type: 'tel',
                            name: 'bic',
                            required: this.fields.includes('bic'),
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'purpose',
                            type: 'tel',
                            name: 'purpose',
                            required: this.fields.includes('purpose')
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'iban',
                            type: 'tel',
                            name: 'iban',
                            required: this.fields.includes('iban')
                         }));

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-name',
                            placeholder: 'account holder',
                            type: 'tel',
                            name: 'account_holder',
                            required: this.fields.includes('account_holder')
                         }));

                },

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                ideal: function($form) {

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'bic',
                            type: 'tel',
                            name: 'bic',
                            required: this.fields.includes('bic'),
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'description',
                            type: 'tel',
                            name: 'description',
                            required: this.fields.includes('description')
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-name ',
                            placeholder: 'language',
                            type: 'tel',
                            name: 'language',
                            required: this.fields.includes('language')
                         }));

                },

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                poli: function($form) {},

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                missingLoader: function($form) {

                    console.log('error');

                },











                /**
                 * Content visible
                 *
                 * @return     {boolean}
                 */
                contentVisible: function() {

                    return true;

                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {

                    var $form = $('#cko-alternative-form');
                    console.log($form.valid());

                    return false;

                },

                /**
                 * Render form.
                 *
                 * @return     {boolean}
                 */
                onSubmit: function(data, events) {

                    // On submit
console.log('on submit', data, events);

                },

            }
        );
    }
);
