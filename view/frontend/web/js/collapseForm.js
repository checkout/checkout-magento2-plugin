require([
    'jquery',
    'mage/url'
    ], function($, url) {
    'use strict';

    // Prepare the required variables
    var $form = $('#cko-form-holder form');
    var $formHolder = $('#cko-form-holder');
    var $addFormButton = $('#show-add-cc-form');
    var $submitFormButton = $('#add-new-cc-card');
    var ckoPublicKey = $('#cko-public-key').val();
    var ckoTheme = $('#cko-theme').val();

    $(document).ready(function() {

        // Add card controls
        $addFormButton.click(function () {
            $formHolder.show();
            $addFormButton.hide();
        });

        // Submit card form controls
        $submitFormButton.click(function (e) {
            e.preventDefault();
            Checkout.submitCardForm();
        });

        // Initialise the embedded form
        Checkout.init({
            publicKey: ckoPublicKey,
            value: 1,
            currency: "USD",
            appMode: 'embedded',
            appContainerSelector: '#embeddedForm',
            theme: ckoTheme,
            cardTokenised: function(event) {
                // Perform the charge via ajax
                chargeWithCardToken(event.data);
            }
        });

        // Card storage function
        function chargeWithCardToken(ckoResponse) {

            // Set the storage controller URL
            var storageUrl = url.build('checkout_com/cards/store');

            // Prepare the request data
            var requestObject = {"ckoCardToken": ckoResponse.cardToken};

            // Perform the storage request
            $.ajax({
                type: "POST",
                url: storageUrl,
                data: JSON.stringify(requestObject),
                success: function(res) {
                },
                error: function (request, status, error) {
                    alert(error);
                }   
            }).done(function (data) {
                window.location.reload();
            });
        }
    });
});
