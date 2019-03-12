<?php

namespace CheckoutCom\Magento2\Model\Methods;

use CheckoutCom\Magento2\Gateway\Config\Config;

class AlternativePaymentMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_alternative_payments';

    /**
     * @var array
     */
    const FIELDS = array('title', 'sepa', 'giropay', 'active', 'public_key');

    /**
     * @var string
     * @overriden
     */
    protected $_code = AlternativePaymentMethod::CODE;

}
