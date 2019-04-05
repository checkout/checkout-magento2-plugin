define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'CheckoutCom_Magento2/js/view/payment/utilities',
    ],
    function (
        Component,
        rendererList,
        Utilities
    ) {

        'use strict';

        var paymentMethods = window.checkoutConfig.payment,
            methods = Utilities.getPaymentMethods();

        methods.forEach(function(element) {

            if(paymentMethods.hasOwnProperty(element) && paymentMethods[element].active) {

                // Render the relevant payment methods
                rendererList.push(
                    {
                        type: element,
                        component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/' + element
                    }
                );

            }

        });

        return Component.extend({});

    }
);
