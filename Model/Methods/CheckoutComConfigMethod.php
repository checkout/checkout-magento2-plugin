<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class CheckoutComConfigMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_configuration';

    /**
     * @var array
     */
    const FIELDS = array('environment', 'public_key', 'payment_action');

    /**
     * @var string
     * @overriden
     */
    protected $_code = CheckoutComConfigMethod::CODE;

}
