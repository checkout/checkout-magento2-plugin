<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model;

/**
 * Class MethodList
 */
class MethodList
{
    /**
     * @var array
     */
    private $methodCodes;

    /**
     * MethodList constructor.
     * @param array $methodCodes
     */
    public function __construct(array $methodCodes = [])
    {
        $this->methodCodes = $methodCodes;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->methodCodes;
    }
}
