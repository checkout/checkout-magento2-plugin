<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

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

            // Add the message
            if (isset($data['message'])) {
                $this->messageManager->addNoticeMessage(__('Message') . ' : ' .  $data['message']);    
            }                     }
    }
}
