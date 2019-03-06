<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class GooglePayMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_google_pay';

    /**
     * @var array
     */
    const FIELDS = array('title', 'merchant_id', 'theme', 'active');

    /**
     * @var string
     * @overriden
     */
    protected $_code = GooglePayMethod::CODE;

}
