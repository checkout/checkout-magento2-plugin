<?php
namespace CheckoutCom\Magento2\Api;
 
interface PaymentInterface {

    /**
     * Performs a charge from an external request.
     *
     * @api
     * @param mixed $data.
     * @return int.
     */
    public function charge($data);
}