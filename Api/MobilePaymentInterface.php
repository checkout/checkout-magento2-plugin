<?php

namespace CheckoutCom\Magento2\Api;

interface MobilePaymentInterface
{
    /**
     * Charge with card token.
     *
     * @api
     * @param mixed $data.
     * @return int.
     */
    public function charge($data);
}