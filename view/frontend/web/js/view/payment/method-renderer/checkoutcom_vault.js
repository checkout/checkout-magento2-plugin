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
                cvvField: '.vault-cvv input',
                containerId: '#vault-container',
                rowSelector: '.cko-vault-card',
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
                var row = this.getActiveRow();
                return row.find('input[name="publicHash"]').val();
            },

            /**
             * @returns {string}
             */
            getCvvValue: function() {
                var row = this.getActiveRow();
                return $.trim(row.find(this.cvvField).val());
            },

            /**
             * @returns {bool}
             */
            isCvvRequired: function() {
                return this.getValue('require_cvv');
            },

            /**
             * @returns {object}
             */
            getActiveRow: function() {
                return $(this.containerId).find('.card-selected');
            },

            /**
             * @returns {bool}
             */
            isCvvValid: function() {
                // Get the active row
                var row = this.getActiveRow();

                if (row.length !== 0) {
                    // Get the CVV string value
                    var strVal = this.getCvvValue();

                    // Get the CVV integer value
                    var intVal = parseInt(strVal) || 0;

                    // Check the validity
                    return intVal > 0 && strVal.length >= 3 && strVal.length <= 4;
                }

                return false;
            },

            /**
             * @returns {bool}
             */
            canEnableButton: function() {
                return !this.isCvvRequired() || (this.isCvvRequired() && this.isCvvValid());
            },

            /**
             * @returns {void}
             */
            enableCvvHandling: function() {
                // Prepare some variables
                var self = this;

                // CVV focus event
                $(self.cvvField).on('focus', function() {
                    $(this).closest(self.rowSelector).trigger('click');
                });

                // CVV change event
                $(self.cvvField).on('change keyup', function() {
                    Utilities.allowPlaceOrder(
                        self.buttonId,
                        self.canEnableButton()
                    );
                });      
            },

            /**
             * @returns {void}
             */
            initWidget: function () {
                // Prepare some variables
                var self = this;

                // Start the loader
                FullScreenLoader.startLoader();
                
                // Send the content AJAX request
                $.ajax({
                    type: "POST",
                    url: Utilities.getUrl('vault/display'),
                    success: function(data) {
                        // Insert the HTML content
                        $(self.containerId).append(data.html).show();
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
                var listItem = $(self.containerId).find(self.rowSelector);

                // Disable place order on click outside       
                $(document).mouseup(function() {
                    Utilities.allowPlaceOrder(
                        self.buttonId, 
                        self.canEnableButton()
                    );
                });

                // Mouse over/out behaviour
                listItem.mouseenter(function() {
                    $(this).addClass('card-on');
                }).mouseleave(function() {
                    $(this).removeClass('card-on');
                });
                          
                // Click behaviour
                listItem.on('click touch', function() {
                    // Items state
                    listItem.removeClass('card-selected');
                    listItem.not(this).find('.vault-cvv input').val('');
                    $(this).addClass('card-selected');

                    // Allow order placement if conditions are matched
                    Utilities.allowPlaceOrder(
                        self.buttonId, 
                        self.canEnableButton()
                    );
                });

                // CVV field events
                if (self.isCvvRequired()) {
                    self.enableCvvHandling();
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

                    // Add the CVV to the payload if needed
                    if (self.isCvvRequired()) {
                        if (!self.isCvvValid()) {
                            Utilities.showMessage(
                                'error',
                                __('The CVV field is invalid.')
                            );

                            return;
                        }
                        else {
                            payload.cvv = self.getCvvValue();
                        }
                    }

                    // Place the order
                    Utilities.placeOrder(payload);
                }
            }
        });
    }
);
