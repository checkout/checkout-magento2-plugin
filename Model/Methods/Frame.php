<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Model\Methods;

use Magento\Payment\Model\Method\AbstractMethod;


class Frame extends AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'checkoutcom_gateway_frame';


    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment code name
     *§§
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CODE;

}
