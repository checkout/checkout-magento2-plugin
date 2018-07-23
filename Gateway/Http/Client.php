<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Gateway\Http;

use Magento\Framework\HTTP\Client\Curl;
use CheckoutCom\Magento2\Gateway\Config\Config;

class Client {

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Client constructor.
     */     
    public function __construct(Curl $curl, Config $config) {
        $this->curl = $curl;
        $this->config = $config;

        // Launch functions
        $this->addHeaders();
    }

    private function addHeaders() {
        $this->curl->addHeader('Authorization', $this->config->getSecretKey());
        $this->curl->addHeader('Content-Type', 'application/json');
    }

    public function post($url, $params) {
        // Send the CURL POST request
        $this->curl->post($url, json_encode($params));

        // Return the response
        return $this->curl->getBody();
    }
   
    public function get($url, $params) {
        // Send the CURL GET request
        $this->curl->post($url, $params);

        // Return the response
        return $this->curl->getBody();     
    }
}
