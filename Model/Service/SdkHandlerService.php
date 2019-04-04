<?php

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Checkout\CheckoutApi;
use \Checkout\Models\Phone;
use CheckoutCom\Magento2\Model\Methods\Method;

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

    /**
     *
     * Common function.
     *
     */

    /**
     * Create address object.
     *
     * @param      array    $array  The array
     *
     * @return     Address
     */
    public static function createAddress($array = array()) {

        $address = new Address();

        $address->address_line1 = Method::getValue('address_line1', $array);
        $address->address_line2 = Method::getValue('address_line2', $array);
        $address->city = Method::getValue('city', $array);
        $address->state = Method::getValue('state', $array);
        $address->zip = Method::getValue('zip', $array);
        $address->country = Method::getValue('country', $array);

        return $address;

    }

    /**
     * Create phone object.
     *
     * @param      array    $array  The array
     *
     * @return     Address
     */
    public static function createPhone($array = array()) {

        $phone = new Phone();

        $phone->country_code = Method::getValue('country_code', $array);
        $phone->number = Method::getValue('number', $array);

        return $phone;

    }

}
