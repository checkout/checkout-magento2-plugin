define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'jquery/ui'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, __) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_apm';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.phtml'
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                    Utilities.setEmail();
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
                 * @returns {void}
                 */
                initWidget: function () {

                    $.ajax({
                        type: "POST",
                        url: Utilities.getUrl('apm/display'),
                        success: function(data) {
                            $('#apm-container')
                            .append(data.html)
                            .accordion({
                                heightStyle: 'content',
                                animate: {
                                    duration: 200
                                }
                            })
                            .show();
                        },
                        error: function (request, status, error) {
                            console.log(error);
                        }
                    });
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function (argv) {

                    var id = $("#apm-container div[aria-selected=true]").attr('id'),
                        $form = $("#cko-apm-form-" + id),
                        data = {methodId: METHOD_ID};

                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if (!argv || (AdditionalValidators.validate() && this.custom(id))) {

                        // Serialize form.
                        $form.serializeArray().forEach(function (e) {
                            data[e.name] = e.value;
                        });

                        Utilities.placeOrder(data, this.handleSuccess, this.handleFail);

                    } else {
                        //this.handleFail(data); //@todo: imrpove needed

                        console.log('fail');
                        FullScreenLoader.stopLoader();
                    }
                },

                /**
                 * Custom "before place order" flows.
                 */

                 /**
                  * Dynamic function handler.
                  *
                  * @param      {String}   id      The identifier
                  * @return     {boolean}
                  */
                custom: function(id) {

                    var result = true;
                    if(typeof this[id] == 'function') {
                        result = this[id]();
                    }

                    return result;

                },

                /**
                 * @returns {void}
                 */
                klarna: function () {

                    var self = this;

                    try {

                        Klarna.Payments.authorize(
                            {
                                instance_id: "klarna-payments-instance",
                                auto_finalize: true
                            },
                            {},
                            function(response) {

                                self.placeOrder(false);

                            });

                    } catch(e) {
                        console.log(e); //@todo: improve this
                    }

                    return false;

                }
            }
        );
    }
);
