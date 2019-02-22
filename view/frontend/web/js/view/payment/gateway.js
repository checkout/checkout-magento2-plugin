define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'checkoutcom_magento2',
                component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/gateway'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
