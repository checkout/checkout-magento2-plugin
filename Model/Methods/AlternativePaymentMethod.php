<?php

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Library\HttpHandler;
use Checkout\Models\Product;
use Checkout\Models\Address;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\EpsSource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\KlarnaSource;
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
        $response = null;

        if ($this->validateRequest($method, $currency)) {

             // Create source object
            $source = call_user_func(array($this, $method), $data);
            $payment = $this->createPayment($source, $amount, $currency, $reference, $this->_code);

            // Send the charge request
            $response = $this->apiHandler->checkoutApi->payments()
                                                      ->request($payment);

            return $response;

        }

        return $response;

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
     * @return     IdSource
     */
    protected function sepa($data) {

        $mandate = $this->activateMandate($data['url']);
        $pos = strripos($data['url'], '/');
        $id = substr($data['url'], $pos +1);

        return new IdSource($id);

    }

    /**
     * Activate the mandate.
     *
     * @param      string   $url
     * @return     array
     */
    protected function activateMandate(string $url) {

        $secret = $this->config->getValue('secret_key');
        $options = array(
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-type: ' . HttpHandler::MIME_TYPE_JSON,
                                        'Accept: ' . HttpHandler::MIME_TYPE_JSON,
                                        'Authorization: ' . $secret,
                                        'User-Agent: checkout-magento2-plugin/1.0.0') //@todo: finish this
        );

        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        $content = curl_exec($curl);
        curl_close($curl);

        return json_decode($content, true);

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
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('nl'));
        $source->language = $locale[0];
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
     * @return     KlarnaSource
     */
    protected function klarna($data) {

        $products = array();
        $tax = 0;
        $quote = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {

            $product = new Product();
            $product->name = $item->getName();
            $product->quantity = $item->getQty();
            $product->unit_price = $item->getPriceInclTax() *100;
            $product->tax_rate = $item->getTaxPercent() *100;
            $product->total_amount = $item->getRowTotalInclTax() *100;
            $product->total_tax_amount = $item->getTaxAmount() *100;

            $tax += $product->total_tax_amount;
            $products []= $product;

        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $address = new Address();
        $address->given_name = $billingAddress->getFirstname();
        $address->family_name = $billingAddress->getLastname();
        $address->email = $billingAddress->getEmail();
        //$address->title = $billingAddress->getPrefix();
        $address->street_address = $billingAddress->getStreetLine(1);
        //$address->street_address2 = $billingAddress->getStreetLine(2);
        $address->postal_code = $billingAddress->getPostcode();
        $address->city = $billingAddress->getCity();
        $address->region = $billingAddress->getRegion();
        $address->phone = $billingAddress->getTelephone();
        $address->country = strtolower($billingAddress->getCountry());


        $klarna =  new KlarnaSource($data['authorization_token'],
                                    strtolower($billingAddress->getCountry()),
                                    'en-GB',
                                    $address,
                                    $tax,
                                    $products);

        return $klarna;

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


    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        // If the quote is valid
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
            && count($this->config->getApms()) > 0;
        }
        
        return false;
    }
}
