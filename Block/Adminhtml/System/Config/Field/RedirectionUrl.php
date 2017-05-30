<?php

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
