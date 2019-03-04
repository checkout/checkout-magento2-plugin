<?php

namespace CheckoutCom\Magento2\Helper;


class Logger {

	public static function write($msg) {

		#write log file
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(print_r($msg, 1));

	}

}