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
                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Send the AJAX request
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

                            // Stop the loader
                            FullScreenLoader.stopLoader();
                        },
                        error: function (request, status, error) {
                            console.log(error);

                            // Stop the loader
                            FullScreenLoader.stopLoader();
                        }
                    });
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {

                    var id = $("#apm-container div[aria-selected=true]").attr('id'),
                        $form = $("#cko-apm-form-" + id),
                        data = {methodId: METHOD_ID};

                    // Start the loader
                    FullScreenLoader.startLoader()

                    // Serialize data
                    $("#cko-apm-form-" + id).serializeArray().forEach(function (e) {
                        data[e.name] = e.value;
                    });


                    if (AdditionalValidators.validate() && $form.valid() && this.custom(data)) {
                        Utilities.placeOrder(data, function() {console.log('success');}, function() {console.log('fail');});
                    }

                    FullScreenLoader.stopLoader();

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
                custom: function(data) {

                    var result = true;
                    if(typeof this[data.source] == 'function') {
                        result = this[data.source](data);
                    }

                    return result;

                },

                /**
                 * @returns {boolean}
                 */
                klarna: function (data) {

                    try {

                        Klarna.Payments.authorize({ instance_id: "klarna-payments-instance",
                                                    auto_finalize: true     },
                            {},
                            function(response) {

                                data.authorization_token = response.authorization_token;
                                Utilities.placeOrder(data, function() {console.log('success');}, function() {Utilities.showMessage('error', 'Could not finalize the payment.');;});

                            });

                    } catch(e) {
                        Utilities.showMessage('error', 'Could not finalize the payment.');
                        console.log(e);
                    }

                    return false;

                },

                /**
                 * @returns {boolean}
                 */
                sepa: function (data) {

                    return data.hasOwnProperty('accepted');

                }

            }
        );
    }
);
