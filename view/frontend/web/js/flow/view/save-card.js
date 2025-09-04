/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define(
    [
        'ko',
        'jquery',
        "CheckoutCom_Magento2/js/common/view/payment/utilities",
        'Magento_Checkout/js/model/full-screen-loader',
        'uiComponent'
    ],
    function (ko, $, Utilities, FullScreenLoader, Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'CheckoutCom_Magento2/flow/view/save-card.html',
                containerSelector: '#checkoutcom_flow_container',
                checkboxSelector: '[name="flow_save_card"]',

            },
            initialize: function () {
                this._super();
                let self = this;
                // TO DO MANAGE IS VISIBLE ONLY IF FLOW
                // TO DO MANAGE IS VISIBLE ONLY IF FLOW CARD
                // TO DO MANAGE CHECK UNCHECK
                $('body').on(
                    'click',
                    this.containerSelector + ' ' + this.checkboxSelector,
                    function () {
                        let savedCard = this.checked;
                        $.ajax(
                            {
                                type: "POST",
                                url: Utilities.getUrl("flow/saveCard"),
                                data: {
                                    save: savedCard ? 1 : 0
                                },
                                success: function (data) {
                                    self.animateRender(data);
                                    self.initEvents();
                                    self.checkLastPaymentMethod();
                                },
                                error: function (request, status, error) {
                                    Utilities.log(error);

                                    // Stop the loader
                                    FullScreenLoader.stopLoader();
                                }
                            }
                        );
                    }
                );

                return this;
            }
        });
    }
);
