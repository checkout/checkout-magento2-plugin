define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate'
    ],
    function ($, Component, Utilities, AdditionalValidators, FullScreenLoader, __) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_vault';

        return Component.extend({
            defaults: {
                template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.phtml',
                buttonId: METHOD_ID + '_btn',
                containerId: 'vault-container',
                redirectAfterPlaceOrder: false
            },

            /**
             * @returns {exports}
             */
            initialize: function() {
                this._super();
                Utilities.setEmail();

                return this;
            },

            /**
             * Getters and setters
             */

            /**
             * @returns {string}
             */
            getCode: function() {
                return METHOD_ID;
            },

            /**
             * @returns {string}
             */
            getValue: function(field) {
                return Utilities.getValue(METHOD_ID, field);
            },

            /**
             * @returns {string}
             */
            getPublicHash: function() {
                return $('#vault-container input[name="savedCard"]:checked').val();
            },

            /**
             * @returns {string}
             */
            getCvv: function() {
                return $('#vault-container input[name="savedCard"]:checked')
                .closest('.cko-vault-card')
                .find('.vault-cvv input').val();
            },

            /**
             * @returns {bool}
             */
            isCvvRequired: function() {
                return this.getValue('require_cvv');
            },

            isCvvValid: function(val) {
                return /^\+?(0|[1-9]\d*)$/.test(val) && val.length > 3 ;
            },

            /**
             * @returns {bool}
             */
            canEnableButton: function(row) {
               return !this.isCvvRequired() || 
               (this.isCvvRequired()
               && this.isCvvValid());
            },

            /**
             * @returns {bool}
             */
            buttonNeedsDisabling: function(event, row) {
                return !row.is(event.target)
                && row.has(event.target).length === 0
                && event.target.localName != 'button'
                && $(event.target).parents('.stored-cards').length !== 0;
            },

            /**
             * @returns {void}
             */
            initWidget: function () {
                // Prepare some variables
                var self = this;
                var container = $('#' + self.containerId);

                // Start the loader
                FullScreenLoader.startLoader();
                
                // Send the content AJAX request
                $.ajax({
                    type: "POST",
                    url: Utilities.getUrl('vault/display'),
                    success: function(data) {
                        // Insert the HTML content
                        container.append(data.html).show();
                        self.initEvents();

                        // Stop the loader
                        FullScreenLoader.stopLoader();
                    },
                    error: function (request, status, error) {
                        FullScreenLoader.stopLoader();
                        console.log(error);
                    }
                });
            },

            /**
             * @returns {void}
             */
            initEvents: function () {
                // Prepare some variables
                var self = this;
                var container = $('#' + self.containerId);
                var listIem = container.find('.cko-vault-card');

                // Disable place order on click outside       
                $(document).mouseup(function(e) {
                    if (self.buttonNeedsDisabling(e, listIem)) {
                        Utilities.allowPlaceOrder(self.buttonId, false);
                    }
                });

                // Mouse over/out behaviour
                listIem.mouseenter(function() {
                    $(this).addClass('card-on');
                }).mouseleave(function() {
                    $(this).removeClass('card-on');
                });
                          
                // Click behaviour
                listIem.on('click touch', function() {
                    // Items state
                    listIem.removeClass('card-selected');
                    $(this).addClass('card-selected');

                    // Allow order placement if a card is selected
                    if (self.canEnableButton($(this))) {
                        Utilities.allowPlaceOrder(self.buttonId, true);
                    }
                });

                // CVV field events
                if (self.isCvvRequired()) {
                    // Select the parent row on CVV field focus
                    $('.vault-cvv input').on('focus', function() {
                        $(this).closest('.cko-vault-card').trigger('click');
                    });

                    // Check CVV provided
                    $('.vault-cvv input').on('change', function() {
                        if (self.isCvvValid($(this).val())) {
                            Utilities.allowPlaceOrder(self.buttonId, true);
                        }
                    });                    
                }
            },


            /**
             * @returns {void}
             */
            placeOrder: function () {
                var self = this;
                if (AdditionalValidators.validate()) {
                    // Prepare the payload
                    var payload = {
                        methodId: METHOD_ID,
                        publicHash: self.getPublicHash()
                    }

                    // Add the CVV if needed
                    if (self.getValue('require_cvv')) {
                        var cvv = self.getCvv();
                        if ($.trim(cvv).length == 0) {
                            Utilities.showMessage(
                                'error',
                                __('The CVV field is required.')
                            );

                            return;
                        }
                        else {
                            payload.cvv = self.getCvv();
                        }
                    }

                    // Place the order
                    Utilities.placeOrder(payload);
                }
            }
        });
    }
);
