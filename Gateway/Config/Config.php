<?php

namespace CheckoutCom\Magento2\Gateway\Config;


use Magento\Checkout\Model\Session;
use \CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\AlternativePaymentMethod;
use \CheckoutCom\Magento2\Model\Methods\GooglePayMethod;
use \CheckoutCom\Magento2\Model\Methods\CheckoutComConfigMethod;
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
    const CODE_CONFIGURATION = CheckoutComConfigMethod::CODE;

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

        if ($this->getMethodValue('active_module')) {

            foreach (Config::PAYMENT_METHODS as $method => $fields) {

                $this->setMethodCode($method);
                $enabled = $this->getValue('active');
                if($enabled) {
                    $methods []= $method;
                }

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

            $values[$field] = $this->getMethodValue($field,
                                                    in_array($field, CheckoutComConfigMethod::FIELDS) ? static::CODE_CONFIGURATION : $method);

        }

        return $values;

    }

    /**
     * Retrieve a value from a method.
     *
     * @param string $value
     * @param string $method
     * @return mixed
     */
    public function getMethodValue($field, $method = Config::CODE_CONFIGURATION)
    {

        $this->setMethodCode($method);
        $value = $this->getValue($field);
        return $this->modifier($value, $field, $method);

    }


    /**
     * Modify value based on the model and field.
     *
     * @param      mixed  $value   The value
     * @param      string  $field   The field
     * @param      string  $method  The method
     *
     * @return     mixed
     */
    protected function modifier($value, $field, $method) {

        if($field === 'public_key') {
            return $this->encryptor->decrypt($value);
        }

        switch ($method) {
            case CheckoutComConfigMethod::CODE:
                $value = CheckoutComConfigMethod::modifier($value, $field);
                break;

            case CardPaymentMethod::CODE:
                $value = CardPaymentMethod::modifier($value, $field);
                break;

            case AlternativePaymentMethod::CODE:
                $value = AlternativePaymentMethod::modifier($value, $field);
                break;

            case GooglePayMethod::CODE:
                $value = GooglePayMethod::modifier($value, $field);
                break;

            case ApplePayMethod::CODE:
                $value = ApplePayMethod::modifier($value, $field);
                break;
        }

        return $value;

    }

}
