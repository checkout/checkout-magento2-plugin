<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\EpsSource;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\KlarnaSource;
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
        AlternativePaymentMethod::PAYMENT_KLARNA => 'Klarna',
        AlternativePaymentMethod::PAYMENT_SOFORT => 'Sofort',
        AlternativePaymentMethod::PAYMENT_EPS => 'EPS'
    );

    /**
     * @var array
     */
    const SUPPORTED_CURRENCIES = array(
        'EUR' => array(AlternativePaymentMethod::PAYMENT_SEPA,
                        AlternativePaymentMethod::PAYMENT_GIROPAY,
                        AlternativePaymentMethod::PAYMENT_IDEAL,
                        AlternativePaymentMethod::PAYMENT_KLARNA,
                        AlternativePaymentMethod::PAYMENT_SOFORT,
                        AlternativePaymentMethod::PAYMENT_EPS),
        'USD' => array(AlternativePaymentMethod::PAYMENT_ALIPAY,
                        AlternativePaymentMethod::PAYMENT_BOLETO),
        'BRL' => array(AlternativePaymentMethod::PAYMENT_BOLETO),
        'AUD' => array(AlternativePaymentMethod::PAYMENT_POLI),
        'NZD' => array(AlternativePaymentMethod::PAYMENT_POLI),
        'DKK' => array(AlternativePaymentMethod::PAYMENT_KLARNA),
        'GBP' => array(AlternativePaymentMethod::PAYMENT_KLARNA),
        'NOK' => array(AlternativePaymentMethod::PAYMENT_KLARNA),
        'SEK' => array(AlternativePaymentMethod::PAYMENT_KLARNA)
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

        } else {
\CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->sendPaymentRequest:: Payment not supported');
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

        return isset(AlternativePaymentMethod::SUPPORTED_CURRENCIES[$currency]) && in_array($method, AlternativePaymentMethod::SUPPORTED_CURRENCIES[$currency]);

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
    protected function sepa($data) {


        $this->activateSepa();









//@todo: make sepa; this will require a separate flow
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->sepa');
        return new IdSource('t');
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected function alipay($data) {
        return new AlipaySource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     BoletoSource
     */
    protected function boleto($data) {
        return new BoletoSource($data['name'], $data['birthDate'], $data['cpf']);
    }

    /**
     * Create source.
     *
     * @param      $data  The source
     *
     * @return     GiropaySource
     */
    protected function giropay(array $data) {
        $source = new GiropaySource(__('Payment request from %1', $this->config->getStoreName()),
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
    protected function ideal($data) {
        $source = new IdealSource($data['bic'],
                                  __('Payment request from %1', $this->config->getStoreName()));
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
    protected function poli($data) {
        return new PoliSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected function sofort($data) {
        return new SofortSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected function klarna($data) {
        \CheckoutCom\Magento2\Helper\Logger::write('AlternativePaymentMethod->klarna');
        return new KlarnaSource();
    }

    /**
     * Create source.
     *
     * @param      $source  The source
     *
     * @return     TokenSource
     */
    protected function eps($data) {
        return new EpsSource(__('Payment request from %1', $this->config->getStoreName()));
    }




    /**
     * Methods
     *
     * @param      \Magento\Payment\Model\InfoInterface             $payment  The payment
     *
     * @throws     \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return     self                                             ( description_of_the_return_value )
     */


    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Check the status
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available.'));
        }

        // Process the void request
        $response = $this->apiHandler->voidTransaction($payment);
        if (!$response || !$response->isSuccessful()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void request could not be processed.'));
        }

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check the status
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // Process the refund request
        $response = $this->apiHandler->refundTransaction($payment, $amount);
        if (!$response || !$response->isSuccessful()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund request could not be processed.'));
        }

        return $this;
    }

}
