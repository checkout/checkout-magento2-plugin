<?php

namespace CheckoutCom\Magento2\Helper;


class Logger {

	public static function write($msg) {

		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkoutcom_magento2.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(print_r($msg, 1));

	}

}