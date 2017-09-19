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
		// Live search
		$("#userCardsSearch").on("keyup", function() {
			var filter = $(this).val().toUpperCase();
			$('#cardsTable tr').not('thead tr').each(function() {
				if ($(this).find('td').text().toUpperCase().indexOf(filter) > -1) {
					$(this).show();
				} 
				else {
					$(this).hide();
				}
			});
		});
	});
});