/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
require([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader'
    ], function($, url, fullScreenLoader) {
    'use strict';

    // Prepare the required variables
    var $form = document.getElementById('embeddedForm');
    var $formHolder = $('#cko-form-holder');
    var $addFormButton = $('#show-add-cc-form');
    var $submitFormButton = $('#add-new-cc-card');
    var css_file = $('#cko-css-file').val();
    var custom_css = $('#cko-custom-css').val();
    var ckoPublicKey = $('#cko-public-key').val();
    var ckoTheme = $('#cko-theme').val();
    var ckoThemeOverride = ((custom_css) && custom_css !== '' && css_file == 'custom') ? custom_css : undefined;

    $(document).ready(function() {
        // Add card controls
        $addFormButton.click(function () {
            $formHolder.show();
            $addFormButton.hide();
        });

        // Submit card form controls
        $submitFormButton.click(function(e) {
            e.preventDefault();
            fullScreenLoader.startLoader();
            Frames.submitCard();
        });

        // Initialise the embedded form
        Frames.init({
            publicKey: ckoPublicKey,
            containerSelector: '#cko-form-holder',
            theme: ckoTheme,
            themeOverride: ckoThemeOverride,
            cardValidationChanged: function() {
                $submitFormButton.prop("disabled", !Frames.isCardValid());
            },
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
            var requestObject = { "ckoCardToken": ckoResponse.cardToken };

            // Perform the storage request
            $.ajax({
                type: "POST",
                url: storageUrl,
                data: JSON.stringify(requestObject),
                success: function(res) {},
                error: function(request, status, error) {
                    alert(error);
                }
            }).done(function(data) {
                fullScreenLoader.stopLoader();
                window.location.reload();
            });
        }
    });
});
