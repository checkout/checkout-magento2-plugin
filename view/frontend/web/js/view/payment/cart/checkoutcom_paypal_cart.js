/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader',
], function($, ko, Component, customerData, customer, Url, FullScreenLoader) {
    'use strict';

    return Component.extend({
        isVisible: ko.observable(false),
        chkPayPalOrderid: null,
        chkPayPalContextId: null,

        initialize: function() {
            this._super();
            let cartData = customerData.get('cart');

            this.isVisible(cartData()['summary_count'] > 0
                && ((cartData()['isGuestCheckoutAllowed']) ||
                    (!cartData()['isGuestCheckoutAllowed'] &&
                        customer.isLoggedIn()))
                && cartData()['checkoutcom_paypal']
                && cartData()['checkoutcom_paypal']['express_cart'] === '1'
                && cartData()['checkoutcom_paypal']['active'] === '1',
            );

            if (this.isVisible) {
                this.setCkcContext();
            }

            cartData.subscribe((updatedCart) => {
                if (typeof window.checkoutConfig !== 'undefined'
                    && typeof window.checkoutConfig.quoteId === 'undefined') {
                    window.checkoutConfig = {...updatedCart['checkoutConfigProvider']};
                }

                this.isVisible(updatedCart['summary_count'] > 0
                    && ((cartData()['isGuestCheckoutAllowed']) ||
                        (!cartData()['isGuestCheckoutAllowed'] &&
                            customer.isLoggedIn()))
                    && updatedCart['checkoutcom_paypal']
                    && updatedCart['checkoutcom_paypal']['express_cart'] ===
                    '1'
                    && updatedCart['checkoutcom_paypal']['active'] === '1');

                if (this.isVisible) {
                    this.setCkcContext();
                }
            });
        },

        setCkcContext: function() {
            let self = this;
            // THIS SCRIPT (Utilities.getUrl('paypal/context')) HAS TO BE CALLED ON THE 'createOrder' event of the paypal button
            // The call must not be async and the 'createOrder' should return the order id
            //Todo: proper way to init, the paypal button must be loaded when
            // #paypal-button-container is on the dom
            setTimeout(function() {
                self.initPaypalButton();
            }, 1500);
        },

        // Has to be factored, it's used 3 times on the whole code
        initPaypalButton: function() {

            let self = this;

            // Prepare Context
            let containerSelector = '#cart-paypal-button-container';
            let datas = {forceAuthorizeMode: 0};
            if ($(containerSelector).length > 0) {
                $.ajax(
                    {
                        type: 'POST',
                        url: Url.build('checkout_com/paypal/context'),
                        showLoader: false,
                        data: datas,
                        success: function(data) {
                            if (typeof data.content !== 'undefined') {
                                self.chkPayPalOrderid = data.content.partner_metadata.order_id;
                                self.chkPayPalContextId = data.content.id;

                                // Init paypal button after getting context order id
                                paypal.Buttons({
                                    createOrder() {
                                        return self.chkPayPalOrderid;
                                    },
                                    onApprove: async function(data) {
                                        // Redirect on paypal reviex page (express checkout)
                                        FullScreenLoader.startLoader();
                                        window.location.href = Url.build(
                                            'checkout_com/paypal/review/contextId/' +
                                            self.chkPayPalContextId);
                                    },
                                }).render(containerSelector);

                            }
                            // Todo else message manager error
                        },
                        error: function(request, status, error) {
                            // Todo message manager error
                        },
                    },
                );
            }
        },
    });
});
