<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Tools {

    const KEY_MODNAME = 'modname';
    const KEY_MODTAG = 'modtag';
    const KEY_MODTAG_APPLE_PAY = 'modtagapplepay';
    const KEY_MODLABEL = 'modlabel';
    const KEY_MODURL = 'modurl';
    const KEY_PARAM_PATH = 'conf/param';

    protected $request;
    protected $scopeConfig;

    public function __construct(Http $request, ScopeConfigInterface $scopeConfig) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->modmeta = $this->getModuleMetadata();
    }

    private function getModuleMetadata() {
        return [
            'tag'          => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODTAG),
            'tagapplepay'  => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODTAG_APPLE_PAY),
            'name'         => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODNAME),
            'label'        => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODLABEL),
            'url'          => $this->scopeConfig->getValue(self::KEY_PARAM_PATH . '/' . self::KEY_MODURL),
        ];
    }

    public function getInputData() {
        // Get all parameters from request
        $params = $this->request->getParams();

        // Sanitize the array
        $params = array_map(function($val) {
            return filter_var($val, FILTER_SANITIZE_STRING);
        }, $params);

        return $params;
    }

    public function formatAmount($amount) {
        return number_format($amount/100, 2);
    }
}