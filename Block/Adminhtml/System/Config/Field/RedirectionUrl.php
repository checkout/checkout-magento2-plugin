<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

class RedirectionUrl extends AbstractCallbackUrl {

    /**
     * Returns the controller url.
     *
     * @return string
     */
    public function getControllerUrl() {
        return 'payment/verify';
    }

}
