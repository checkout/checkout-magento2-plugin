/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

require([
    'jquery', 'imgselect', 'domReady!'
], function($) {

    $(document).ready(function() {
        $('select[name="card_id"] option').each(function(i) {

            // Get the text in the option
            var str = $(this).text().toLowerCase();

            // Get the card types
            var cardTypes = getCardTypes();

            // For each option in the select
            for (var i = 0; i < cardTypes.length; i++) {

                // If the card type is specified
                if (str.indexOf(':' + cardTypes[i].toLowerCase()) >= 0) {
                    // Add teh card image
                    $(this).attr('data-image', require.toUrl('CheckoutCom_Magento2/images/cards/' + cardTypes[i].toLowerCase() + '.png'));
                    // Remove the card type flag
                    $(this).html($(this).html().replace(':' + cardTypes[i], ''));
                }
            }
        });

        // Trigger the image dropdown
        $('select[name="card_id"]').msDropdown();

    });
});


function getCardTypes() {
    return ['AE', 'VI', 'MC', 'DI', 'JCB', 'SM', 'DN', 'MD', 'MI', 'SO', 'UN'];
}