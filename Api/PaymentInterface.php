<?php
namespace CheckoutCom\Magento2\Api;
 
interface PaymentInterface {
    public function charge($data);
}