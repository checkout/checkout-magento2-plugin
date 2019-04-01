<?php

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Checkout\CheckoutApi;

/**
 * Class for sdk handler service.
 */
class SdkHandlerService
{

    protected $config;
    protected $checkout;

	/**
     * Initialize SDK wrapper.
     *
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config  $config  The configuration
     */
    public function __construct(Config $config)
    {

        $this->config = $config;
        $this->checkout = new CheckoutApi($this->config->getMethodValue('secret_key'),
    									  $this->config->getMethodValue('environment'),
    									  $this->config->getMethodValue('public_key'));

    }

    /**
     * By pass function call to the SDK.
     *
     * @param      string  $name   The name
     * @param      array  $args   The arguments
     *
     * @return     mixed
     */
    public function __call($name, $args) {

    	return call_user_func_array(array($this->checkout, $name), $args);

    }

}
