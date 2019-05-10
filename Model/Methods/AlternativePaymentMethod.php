<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\PoliSource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\SofortSource;
use \Checkout\Models\Payments\GiropaySource;
use CheckoutCom\Magento2\Gateway\Config\Config;

class AlternativePaymentMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apm';

    /**
     * @var string
     */
    const PAYMENT_SEPA = 'sepa';

    /**
     * @var string
     */
    const PAYMENT_ALIPAY = 'alipay';

    /**
     * @var string
     */
    const PAYMENT_BOLETO = 'boleto';

    /**
     * @var string
     */
    const PAYMENT_GIROPAY = 'giropay';

    /**
     * @var string
     */
    const PAYMENT_IDEAL = 'ideal';

    /**
     * @var string
     */
    const PAYMENT_POLI = 'poli';

    /**
     * @var string
     */
    const PAYMENT_QIWI = 'qiwi';

    /**
     * @var string
     */
    const PAYMENT_SAFETYPAY = 'safetypay';

    /**
     * @var string
     */
    const PAYMENT_KLARNA = 'klarna';

    /**
     * @var string
     */
    const PAYMENT_SOFORT = 'sofort';

    /**
     * @var string
     */
    const PAYMENT_EPS = 'eps';

    /**
     * @var array
     */
    const PAYMENT_LIST = array(
        AlternativePaymentMethod::PAYMENT_SEPA => 'SEPA',
        AlternativePaymentMethod::PAYMENT_ALIPAY => 'Alipay',
        AlternativePaymentMethod::PAYMENT_BOLETO => 'Boleto',
        AlternativePaymentMethod::PAYMENT_GIROPAY => 'Giropay',
        AlternativePaymentMethod::PAYMENT_IDEAL => 'iDEAL',
        AlternativePaymentMethod::PAYMENT_POLI => 'Poli',
        //AlternativePaymentMethod::PAYMENT_QIWI => 'Qiwi',
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => 'SafetyPay',
        //AlternativePaymentMethod::PAYMENT_KLARNA => 'Klarna',
        AlternativePaymentMethod::PAYMENT_SOFORT => 'Sofort',
        //AlternativePaymentMethod::PAYMENT_EPS => 'EPS'
    );

    /**
     * @var array
     */
    const SUPPORTED_CURRENCIES = array(
        AlternativePaymentMethod::PAYMENT_SEPA => array('EUR'),
        AlternativePaymentMethod::PAYMENT_ALIPAY => array('USD'),
        AlternativePaymentMethod::PAYMENT_BOLETO => array('USD', 'BRL'),
        AlternativePaymentMethod::PAYMENT_GIROPAY => array('EUR'),
        AlternativePaymentMethod::PAYMENT_IDEAL => array('EUR'),
        AlternativePaymentMethod::PAYMENT_POLI => array('AUD', 'NZD'),
        //AlternativePaymentMethod::PAYMENT_QIWI => array('USD', 'EUR'),
        //AlternativePaymentMethod::PAYMENT_SAFETYPAY => array('EUR'),
        //AlternativePaymentMethod::PAYMENT_KLARNA => array('EUR', 'DKK', 'GBP', 'NOK', 'SEK'),
        AlternativePaymentMethod::PAYMENT_SOFORT => array('EUR'),
        //AlternativePaymentMethod::PAYMENT_EPS => array('EUR')
    );

    /**
     * @var string
     * @overriden
     */
    protected $_code = self::CODE;


    /**
     * Methods
     */

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest(array $data, $amount, $currency, $reference = '') {

        $method = $data['source'];

        if ($this->validateRequest($method, $currency)) {

             // Create source object
            $source = call_user_func(array($this, $data['source']), $data);
            $payment = $this->createPayment($source, $amount, $currency, $reference);

            // Send the charge request
            $response = $this->apiHandler->checkoutApi->payments()
                                                      ->request($payment);

            return $response;

        }

    }

    /**
     * Methods
     */

    /**
     * Verify if country and currency are supported by the payment method.
     *
     * @param      string  $method    The method
     * @param      string  $currency  The currency
     *
     * @return     bool
     * @todo       validateCountry($method, $country)!
     */
    protected function validateRequest(string $method, string $currency) {

        return $this->validateCurrency($method, $currency); // && $this->validateCountry();

    }


    /**
     * Verify if currency is supported.
     *
     * @param      string  $method    The method
     * @param      string  $currency  The currency
     *
     * @return     bool
     */
    protected function validateCurrency(string $method, string $currency) {

        return isset(AlternativePaymentMethod::SUPPORTED_CURRENCIES[$method]) && in_array($currency, AlternativePaymentMethod::SUPPORTED_CURRENCIES[$method]);

    }




    /**
     * API related.
     */

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function sepa($data) {
//@todo: make sepa; this will require a separate flow
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->sepa');

    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function alipay($data) {
        return new AlipaySource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     BoletoSource
     */
    protected static function boleto($data) {
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->boleto');
        return new BoletoSource('asdads',
                                $data['birthDate'],
                                $data['cpf']);
    }

    /**
     * Create source.
     *
     * @param      $data  The source
     *
     * @return     GiropaySource
     */
    protected function giropay(array $data) {
        $source = new GiropaySource($data['purpose'],
                                    static::getValue('bic', $data));
        $source->iban = static::getValue('iban', $data);
        return $source;
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function ideal($data) {
        $source = new IdealSource($data['bic'],
                                  $data['description']);
        $source->language = static::getValue('language', $data);
        return $source;
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function poli($data) {
        return new PoliSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function sofort($data) {
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->sofort');
        return new SofortSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function klarna($data) {
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->klarna');
        return new PoliSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected static function eps($data) {
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->eps');
        return new PoliSource();
    }

}
