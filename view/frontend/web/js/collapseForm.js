require([
    'jquery',
    'underscore'
    ], function($, _) {
    'use strict';

    $(document).ready(function() {
        var
            $form = $('#add-cc-form'),
            $formErrors = $('#cc-form-errors'),
            $addFormButton = $('#show-add-cc-form'),
            $closeErrorsButton = $('#closebtn'),
            $submitForm = $('#add-new-cc-card'),
            cardDetailsAttributes = ['expiryMonth', 'expiryYear', 'last4', 'paymentMethod'];

        $addFormButton.click(function () {
            $form.show();
            $addFormButton.hide();
        });

        $submitForm.click(function(e) {
            $formErrors.empty().hide();

            e.preventDefault();
        });

        function getCardTokenData() {
            var data = {};

            data = {
                expiryMonth: $('#expiry-month').val(),
                expiryYear: $('#expiry-year').val(),
                number: $('#card-number').val(),
                'email-address': $('#customer-email').val(),
                name:  $('#card-holder').val()
            };

            var $cvv = $('#cvv');

            if($cvv.length) {
                data.cvv = $cvv.val();
            }

            return data;
        }

        var sdkUrl = $('#checkout-sdk-url').val();

        window.CKOConfig = {
            debugMode: $('#checkout-is-debug-mode').val() === '1',
            publicKey: $('#checkout-public-key').val(),
            ready: function () {

                $submitForm.click(function() {
                    CheckoutKit.createCardToken(getCardTokenData(), {includeBinData: false}, function(response) {
                        if ('error' !== response.type) {
                            var card = response.card;

                            _.each(cardDetailsAttributes, function(attribute) {
                                $('#checkout-card-' + attribute).val( card[attribute] );
                            });

                            $('#cko-card-token').val(response.id);

                            $form.submit();
                        }
                    });
                });

            },
            apiError: function (event) {
                var msg = '<span id="closebtn" >&times;</span><strong>' + event.data.message + ':</strong><br>' + event.data.errors.join('<br>');

                $formErrors.append(msg).show();

                $closeErrorsButton.click(function () {
                    $formErrors.fadeOut('slow');
                });
            }
        };

        require([sdkUrl], function () {});
    });

});
