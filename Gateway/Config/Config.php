<?php

namespace CheckoutCom\Magento2\Gateway\Config;


use Magento\Checkout\Model\Session;
use \CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\GooglePayMethod;
use \CheckoutCom\Magento2\Model\Methods\ApplePayMethod;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

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
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * List of payment methods by Checkout.com
     * @var array
     */
    const PAYMENT_METHODS = array(  CardPaymentMethod::CODE        => CardPaymentMethod::FIELDS,
                                    AlternativePaymentMethod::CODE => AlternativePaymentMethod::FIELDS,
                                    GooglePayMethod::CODE          => GooglePayMethod::FIELDS,
                                    ApplePayMethod::CODE           => ApplePayMethod::FIELDS);


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN,
        EncryptorInterface $encryptor
    ) {

        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->encryptor = $encryptor;

    }

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
        foreach (static::PAYMENT_METHODS[$method] as &$field) {

            if($field === 'public_key') {
                $this->setMethodCode(static::CODE_CARD);
            } else {
                $this->setMethodCode($method);
            }

            $values[$field] = $this->modifier($method, $field, $this->getValue($field));

        }

        return $values;

    }


    /**
     * Modify value based on the model and field.
     *
     * @param      string  $method  The method
     * @param      string  $field   The field
     * @param      mixed  $value   The value
     *
     * @return     mixed
     */
    protected function modifier($method, &$field, $value) {

        if($field === 'public_key') {
            return $this->encryptor->decrypt($value);
        }

        switch ($method) {
            case CardPaymentMethod::CODE:
                $value = CardPaymentMethod::modifier($field, $value);
                break;

            case AlternativePaymentMethod::CODE:
                $value = AlternativePaymentMethod::modifier($field, $value);
                break;

            case GooglePayMethod::CODE:
                $value = GooglePayMethod::modifier($field, $value);
                break;

            case ApplePayMethod::CODE:
                $value = ApplePayMethod::modifier($field, $value);
                break;
        }

        return $value;

    }

}
