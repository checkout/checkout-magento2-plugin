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
            'Magento_Checkout/js/model/place-order': 'CheckoutCom_Magento2/js/model/place-order',
            'Magento_Checkout/js/model/error-processor': 'CheckoutCom_Magento2/js/model/error-processor',
        }
    }
};