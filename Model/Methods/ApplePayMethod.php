<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class ApplePayMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apple_pay';

    /**
     * @var array
     */
    const FIELDS = array('title', 'enabled', 'certificate', 'certificate_key', 'theme', 'active');

    /**
     * @var string
     * @overriden
     */
    protected $_code = ApplePayMethod::CODE;

}
