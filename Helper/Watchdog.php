<?php

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;

class Watchdog {

    protected $messageManager;
    protected $config;

    public function __construct(ManagerInterface $messageManager, Config $config) {
        $this->messageManager = $messageManager;
        $this->config = $config;
    }

    public function bark($data) {
        if ($this->config->isDebugMode()) {
            // Add the response code
            if (isset($data['responseCode'])) {
                $this->messageManager->addNoticeMessage(__('Response code') . ' : ' .  $data['responseCode']);
            }

            // Add the response message
            if (isset($data['responseMessage'])) {
                $this->messageManager->addNoticeMessage(__('Response message') . ' : ' .  $data['responseMessage']);    
            }   

            // Add the error code
            if (isset($data['errorCode'])) {
                $this->messageManager->addNoticeMessage(__('Error code') . ' : ' .  $data['errorCode']);    
            }  

            // Add the error code
            if (isset($data['status'])) {
                $this->messageManager->addNoticeMessage(__('Status') . ' : ' .  $data['status']);    
            }                    
        }
    }
}
