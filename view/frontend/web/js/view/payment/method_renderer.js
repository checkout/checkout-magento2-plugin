/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

define(
    [
        'uiComponent',
        'CheckoutCom_Magento2/js/view/payment/adapter',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        Adapter,
        rendererList
    ) {
        'use strict';

        var paymentMethod = window.checkoutConfig.payment[Adapter.getCode()];
        var paymentMethodApplePay = window.checkoutConfig.payment[Adapter.getCodeApplePay()];

        if (paymentMethod.isActive) {
            rendererList.push({
                type: Adapter.getCode(),
                component: Adapter.getName() + '/js/view/payment/method-renderer/' + Adapter.getPaymentConfig()['getIntegration']
            });
        }

        if (paymentMethodApplePay.isActive) {
            rendererList.push({
                type: Adapter.getCodeApplePay(),
                component: Adapter.getName() + '/js/view/payment/method-renderer/apple-pay'
            });
        }

        return Component.extend({});
    }
);