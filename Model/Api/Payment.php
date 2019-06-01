<?php
namespace CheckoutCom\Magento2\Model\Api;
 
class Payment implements \CheckoutCom\Magento2\Api\PaymentInterface {
    public function charge($data) {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/api.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($data, 1));

    }
}