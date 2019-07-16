define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'CheckoutCom_Magento2/js/view/payment/config-loader'
    ],
    function (
        Component,
        rendererList,
        Config
    ) {
        // Render the active payment methods
        for (var method in Config) {
            rendererList.push(
                {
                    type: method,
                    component: 'CheckoutCom_Magento2/js/view/payment/method-renderer/' + method
                }
            );
        }

        return Component.extend({});
    }
);
