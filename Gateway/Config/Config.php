<?php

namespace CheckoutCom\Magento2\Gateway\Config;


class Config
{

    /**
     * @var string
     */
    const CODE_CARD = 'checkoutcom_magento2_redirect_method';

    /**
     * @var string
     */
    const CODE_ALTERNATIVE = 'checkoutcom_alternative_payments';

    /**
     * @var string
     */
    const CODE_GOOGLE = 'checkoutcom_google_pay';

    /**
     * @var string
     */
    const CODE_APPLE = 'checkoutcom_apple_pay';

    /**
     * Config constructor.
     */
    public function __construct() {

    }

    public function getConfig() {
        return [];
    }

}
