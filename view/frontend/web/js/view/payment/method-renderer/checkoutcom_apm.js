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
                        showLoader: true,
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
                placeOrder: function () {

                    var id = $("#apm-container div[aria-selected=true]").attr('id'),
                        $form = $("#cko-apm-form-" + id),
                        data = {methodId: METHOD_ID};

                    // Start the loader
                    FullScreenLoader.startLoader()

                    $("#cko-apm-form-" + id).serializeArray().forEach(function (e) {
                        data[e.name] = e.value;
                    });


                    if (AdditionalValidators.validate() && this.custom(data)) { //@todo: addtional validators are not working

                        Utilities.placeOrder(data, function() {console.log('success');}, function() {console.log('fail');});

                    } else {

console.log('fail'); //@todo: handle error

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
                 * @returns {void}
                 */
                klarna: function (data) {

                    try {

                        Klarna.Payments.authorize({ instance_id: "klarna-payments-instance",
                                                    auto_finalize: true     },
                            {},
                            function(response) {

                                data.authorization_token = response.authorization_token;
                                Utilities.placeOrder(data, function() {console.log('success');}, function() {console.log('fail');});

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
