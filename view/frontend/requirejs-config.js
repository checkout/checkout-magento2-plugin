var config = {
    map: {
        '*': {
            framesjs: 'https://cdn.checkout.com/js/framesv2.min.js',
            Klarna: 'https://x.klarnacdn.net/kp/lib/v1/api.js',
            googlepayjs: 'https://pay.google.com/gp/p/js/pay.js'
        }
    }, config: {
        mixins: {
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'CheckoutCom_Magento2/js/model/checkout-data-resolver': true
            },
            'Magento_Tax/js/view/checkout/summary/grand-total': {
                'CheckoutCom_Magento2/js/model/grand-total-hide': true
            }
        }
    }
};