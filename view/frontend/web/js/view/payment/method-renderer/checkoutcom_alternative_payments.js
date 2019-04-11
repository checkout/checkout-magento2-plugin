define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, __) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_alternative_payments';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID
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
                 * @returns {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @returns {string}
                 */
                getValue: function(field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * @returns {boolean}
                 */
                isAvailable: function () {
                    return true;
                },

                /**
                 * @returns array
                 */
                getAlternativePaymentsList: function () {
                    return {};
                    var list = JSON.parse(this.getValue('alternatives')),
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
                 * Content visible
                 *
                 * @return     {boolean}
                 */
                contentVisible: function() {

                    return true;

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
                            type: 'text',
                            name: 'cpf',
                            required: this.fields.includes('cpf'),
                            pattern: '.{11,11}'
                         }));

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-calendar',
                            placeholder: __('birthdate'),
                            type: 'text',
                            name: 'birthDate',
                            required: this.fields.includes('birthDate'),
                            pattern: '\\d{1,2}/\\d{1,2}/\\d{4}',
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
                            type: 'text',
                            name: 'purpose',
                            required: this.fields.includes('purpose')
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
                            type: 'text',
                            name: 'bic',
                            required: this.fields.includes('bic'),
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'description',
                            type: 'text',
                            name: 'description',
                            required: this.fields.includes('description')
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-name ',
                            placeholder: 'language',
                            type: 'text',
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
                sepa: function($form) {

                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'bic',
                            type: 'text',
                            name: 'bic',
                            required: this.fields.includes('bic'),
                         }));


                    $form.append(Utilities.createInput({
                            icon: 'ckojs-card',
                            placeholder: 'account iban',
                            type: 'text',
                            name: 'account_iban',
                            required: this.fields.includes('account_iban'),
                         }));

                },

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                sofort: function($form) {},

                /**
                 * Render custom fields.
                 *
                 * @return     {boolean}
                 */
                missingLoader: function($form) {

                    console.log(__('Alternative payment loader not implemented.'));

                },













                /**
                 * @returns {void}
                 */
                placeOrder: function () {

                    var $form = $('#cko-alternative-form'),
                        data = {};

                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if ($form.valid() && AdditionalValidators.validate()) {

                        // Serialize form.
                        $form.serializeArray().forEach(function (e) {
                            data[e.name] = e.value;
                        });

                        Utilities.placeOrder(data, this.handleSuccess, this.handleFail);

                    } else {

                        this.handleFail(data); //@todo: imrpove needed
                        FullScreenLoader.stopLoader();

                    }

                    return false;

                },



                /**
                 * HTTP handlers
                 */



                handleSuccess: function(res) {
console.log('success', res);
                    FullScreenLoader.stopLoader();
                },

                handleFail: function(res) {
console.log('fail', res);
                    FullScreenLoader.stopLoader();
                }

            }
        );
    }
);
