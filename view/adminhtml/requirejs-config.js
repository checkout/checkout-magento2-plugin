/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

var config = {
    map: {
        '*': {
            //hideseeksearch: 'CheckoutCom_Magento2/js/hideseek/jquery.hideseek.min',
        }
    },
    paths: {
	    imgselect: 'CheckoutCom_Magento2/js/msdropdown/jquery.dd.min',
  	},
    shim: {
        imgselect: {
            deps: ['jquery']
        }
    }
};