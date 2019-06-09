<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Methods;

use \Checkout\Library\HttpHandler;
use \Checkout\Models\Product;
use \Checkout\Models\Address;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\EpsSource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\KlarnaSource;
use \Checkout\Models\Payments\SofortSource;
use \Checkout\Models\Payments\GiropaySource;

class AlternativePaymentMethod extends Method
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apm';

    /**
     * @var string
     * @overriden
     */
    protected $_code = self::CODE;

    /**
     * @var ShopperHandlerService
     */
    protected $shopperHandler;


    /**
     * Magic Methods
     */

    /**
     * Constructor.
     *
     * @param      \Magento\Framework\Model\Context                         $context                 The context
     * @param      \Magento\Framework\Registry                              $registry                The registry
     * @param      \Magento\Framework\Api\ExtensionAttributesFactory        $extensionFactory        The extension factory
     * @param      \Magento\Framework\Api\AttributeValueFactory             $customAttributeFactory  The custom attribute factory
     * @param      \Magento\Payment\Helper\Data                             $paymentData             The payment data
     * @param      \Magento\Framework\App\Config\ScopeConfigInterface       $scopeConfig             The scope configuration
     * @param      \Magento\Payment\Model\Method\Logger                     $logger                  The logger
     * @param      \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress     $remoteAddress           The remote address
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config              $config                  The configuration
     * @param      \CheckoutCom\Magento2\Model\Service\ApiHandlerService    $apiHandler              The api handler
     * @param      \CheckoutCom\Magento2\Model\Service\QuoteHandlerService  $quoteHandler            The quote handler
     * @param      \CheckoutCom\Magento2\Model\Service\ShopperHandlerService   $shopperHandler             The card handler
     * @param      \Magento\Framework\Model\ResourceModel\AbstractResource  $resource                The resource
     * @param      \Magento\Framework\Data\Collection\AbstractDb            $resourceCollection      The resource collection
     * @param      array                                                    $data                    The data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $remoteAddress,
            $config,
            $apiHandler,
            $quoteHandler,
            $resource,
            $resourceCollection,
            $data
        );

        $this->shopperHandler = $shopperHandler;

    }

    /**
     * Methods
     */

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest(array $data, $amount, $currency, $reference = '') {

        $method = $data['source'];
        $response = null;

        if ($this->validateCurrency($method, $currency)) {

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
     * Verify if currency is supported.
     *
     * @param      string  $method    The method
     * @param      string  $currency  The currency
     *
     * @return     bool
     */
    protected function validateCurrency(string $method, string $currency) {

        $apms = $this->config->getApms();
        $valid = false;

        foreach ($apms as $apm) {
            if($apm['value'] === $method) {
                $valid = strpos($apm['currencies'], $currency) !== false;
            }
        }

        return $valid;

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

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        $product = new Product();
        $product->name = $shipping->getShippingDescription();
        $product->quantity = 1;
        $product->unit_price = $shipping->getShippingInclTax() *100;
        $product->tax_rate = $shipping->getTaxPercent() *100;
        $product->total_amount = $shipping->getShippingAmount() *100;
        $product->total_tax_amount = $shipping->getTaxAmount() *100;
        $product->type = 'shipping_fee';

        $tax  += $product->total_tax_amount;
        $products []= $product;

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
