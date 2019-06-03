<?php

namespace CheckoutCom\Magento2\Helper;

class Logger {

    /**
     * @var ManagerInterface
     */
	protected $messageManager;
	
    /**
     * @var Config
     */
	protected $config;
	
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
	}
	
	public function write($msg) {
        if ($this->config->getValue('debug') && $this->config->getValue('file_logging')) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkoutcom_magento2.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($msg, 1));
        }
	}

	public function display($msg) {
        if ($this->config->getValue('debug') && $this->config->getValue('gateway_responses')) {
            $this->messageManager->addSuccessMessage($msg);
        }
	}
}