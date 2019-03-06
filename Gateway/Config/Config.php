<?php

namespace CheckoutCom\Magento2\Gateway\Config;


use Magento\Checkout\Model\Session;
use \CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\GooglePayMethod;
use \CheckoutCom\Magento2\Model\Methods\ApplePayMethod;

class Config extends \Magento\Payment\Gateway\Config\Config
{

    /**
     * @var string
     */
    const CODE_CARD = CardPaymentMethod::CODE;

    /**
     * @var string
     */
    const CODE_ALTERNATIVE = AlternativePaymentMethod::CODE;

    /**
     * @var string
     */
    const CODE_GOOGLE = GooglePayMethod::CODE;

    /**
     * @var string
     */
    const CODE_APPLE = ApplePayMethod::CODE;

    /**
     * List of payment methods by Checkout.com
     * @var array
     */
    const PAYMENT_METHODS = array(  CardPaymentMethod::CODE        => CardPaymentMethod::FIELDS,
                                    AlternativePaymentMethod::CODE => AlternativePaymentMethod::FIELDS,
                                    GooglePayMethod::CODE          => GooglePayMethod::FIELDS,
                                    ApplePayMethod::CODE           => ApplePayMethod::FIELDS);


    /**
     * {@inheritdoc}
     */
    public function getConfig() {

        $methods = [];
        foreach($this->getEnabledMethods() as $method) { // Get enabled methods
            $methods[$method] = $this->getMethodValues($method); // Get config values for those methods
        }

        return array('payment' => $methods);

    }


    /**
     * Getters
     **/

    /**
     * Retrieve active system payments
     *
     * @return array
     * @api
     */
    public function getEnabledMethods()
    {

        $methods = [];
        foreach (Config::PAYMENT_METHODS as $method => $fields) {

            $this->setMethodCode($method);
            $enabled = $this->getValue('active');
            if($enabled) {
                $methods []= $method;
            }

        }

        return $methods;

    }

    /**
     * Retrieve configuration for a method
     *
     * @param string $method
     * @return array
     * @api
     */
    public function getMethodValues($method)
    {

        $values = [];
        foreach (static::PAYMENT_METHODS[$method] as $field) {

            $this->setMethodCode($method);
            $values[$field] = $this->getValue($field);

        }

        return $values;

    }

}
