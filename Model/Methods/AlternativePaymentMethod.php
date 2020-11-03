<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
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
use \Checkout\Models\Payments\Source;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\EpsSource;
use \Checkout\Models\Payments\IdealSource;
use \Checkout\Models\Payments\AlipaySource;
use \Checkout\Models\Payments\BoletoSource;
use \Checkout\Models\Payments\KnetSource;
use \Checkout\Models\Payments\FawrySource;
use \Checkout\Models\Payments\BancontactSource;
use \Checkout\Models\Payments\KlarnaSource;
use \Checkout\Models\Payments\SofortSource;
use \Checkout\Models\Payments\GiropaySource;
use \Checkout\Models\Payments\PoliSource;
use \Checkout\Models\Payments\PaypalSource;
use \Checkout\Library\Exceptions\CheckoutHttpException;

/**
 * Class AlternativePaymentMethod
 */
class AlternativePaymentMethod extends AbstractMethod
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apm';

    /**
     * @var string
     */
    public $_code = self::CODE;

    /**
     * @var bool
     */
    public $_canAuthorize = true;

    /**
     * @var bool
     */
    public $_canCapture = true;

    /**
     * @var bool
     */
    public $_canCancel = true;

    /**
     * @var bool
     */
    public $_canCapturePartial = true;

    /**
     * @var bool
     */
    public $_canVoid = true;

    /**
     * @var bool
     */
    public $_canUseInternal = false;

    /**
     * @var bool
     */
    public $_canUseCheckout = true;

    /**
     * @var bool
     */
    public $_canRefund = true;

    /**
     * @var bool
     */
    public $_canRefundInvoicePartial = true;

    /**
     * @var ShopperHandlerService
     */
    public $shopperHandler;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var Logger
     */
    public $ckoLogger;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Curl
     */
    public $curl;

    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var VersionHandler
     */
    public $versionHandler;

    /**
     * AlternativePaymentMethod constructor.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\shopperHandlerService $shopperHandler,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\VersionHandlerService $versionHandler,
        \CheckoutCom\Magento2\Controller\Apm\Display $display,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Client\Curl $curl,
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
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder         = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->_objectManager     = $objectManager;
        $this->invoiceSender      = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->checkoutData       = $checkoutData;
        $this->quoteRepository    = $quoteRepository;
        $this->quoteManagement    = $quoteManagement;
        $this->orderSender        = $orderSender;
        $this->sessionQuote       = $sessionQuote;
        $this->config             = $config;
        $this->shopperHandler     = $shopperHandler;
        $this->apiHandler         = $apiHandler;
        $this->quoteHandler       = $quoteHandler;
        $this->ckoLogger          = $ckoLogger;
        $this->utilities          = $utilities;
        $this->storeManager       = $storeManager;
        $this->curl               = $curl;
        $this->versionHandler     = $versionHandler;
        $this->display            = $display;
    }

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest(array $data, $amount, $currency, $reference = '')
    {
        $method = $data['source'];
        $response = null;

        if ($this->validateCurrency($method, $currency)) {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Create source object
            $source = $this->{$method}($data, $reference);
            $payment = $this->createPayment(
                $source,
                $amount,
                $currency,
                $reference,
                $this->_code,
                $method
            );

            // Send the charge request
            try {
                $response = $api->checkoutApi
                    ->payments()->request($payment);

                return $response;
            } catch (CheckoutHttpException $e) {
                $this->ckoLogger->write($e->getBody());
            }
        }

        return $response;
    }

    /**
     * Creates a payment object.
     *
     * @param      \Checkout\Models\Payments\Source  $source     The source
     * @param      integer                           $amount     The amount
     * @param      string                            $currency   The currency
     * @param      string                            $reference  The reference
     * @param      string                            $methodId   The method identifier
     *
     * @return     \Checkout\Models\Payments\Payment
     */
    public function createPayment($source, $amount, string $currency, string $reference, string $methodId, string $method)
    {
        $payment = null;

        // Create payment object
        $payment = new Payment($source, $currency);

        // Prepare the metadata array
        $payment->metadata['methodId'] = $methodId;

        // Get the quote
        $quote = $this->quoteHandler->getQuote();

        // Add the base metadata
        $payment->metadata = array_merge(
            $payment->metadata,
            $this->apiHandler->getBaseMetadata()
        );
        
        // Set the payment specifications
        $payment->capture = $this->config->needsAutoCapture($this->_code);
        $payment->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $payment->reference = $reference;
        $payment->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $payment->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
        $payment->customer = $this->apiHandler->createCustomer($quote);
        $payment->shipping = $this->apiHandler->createShippingAddress($quote);
        $payment->description = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->getText();
        $payment->payment_type = 'Regular';

        return $payment;
    }

    /**
     * Verify if currency is supported.
     *
     * @param string $method   The method
     * @param string $currency The currency
     *
     * @return bool
     */
    public function validateCurrency(string $method, string $currency)
    {
        $apms = $this->config->getApms();
        $valid = false;
        foreach ($apms as $apm) {
            if ($apm['value'] === $method) {
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
     * @param $source  The source
     *
     * @return IdSource
     */
    public function sepa($data)
    {
        $mandate = $this->activateMandate($data['url']);
        $pos = strripos($data['url'], '/');
        $id = substr($data['url'], $pos +1);

        return new IdSource($id);
    }

    /**
     * Activate the mandate.
     *
     * @param  string $url
     * @return array
     */
    public function activateMandate(string $url)
    {
        // Get the secret key
        $secret = $this->config->getValue('secret_key');

        // Prepare the options
        // Set the CURL headers
        $this->curl->setHeaders([
            'Content-type: ' . HttpHandler::MIME_TYPE_JSON,
            'Accept: ' . HttpHandler::MIME_TYPE_JSON,
            'Authorization: ' . $secret,
            'User-Agent: checkout-magento2-plugin/' . $this->versionHandler->getModuleVersion()
        ]);

        // Set extra CURL parameters
        $this->curl->setOption(CURLOPT_FAILONERROR, false);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

        // Send the request
        $this->curl->post($url, []);

        // Get the response
        $content = $this->curl->getBody();

        // Return the content
        return json_decode($content, true);
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function alipay($data)
    {
        return new AlipaySource();
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return BoletoSource
     */
    public function boleto($data)
    {
        return new BoletoSource($data['name'], $data['birthDate'], $data['cpf']);
    }

    /**
     * Create source.
     *
     * @param $data  The source
     *
     * @return GiropaySource
     */
    public function giropay(array $data)
    {
        $source = new GiropaySource(
            __('Payment request from %1', $this->config->getStoreName())->getText(),
            $this->getValue('bic', $data)
        );
        $source->iban = $this->getValue('iban', $data);
        return $source;
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function ideal($data)
    {
        $source = new IdealSource(
            $data['bic'],
            $data['description']
        );
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('nl_NL'));
        $source->language = $locale[0];
        return $source;
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function paypal($data, $reference)
    {
        $source = new PaypalSource($reference);
        return $source;
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function poli($data)
    {
        return new PoliSource();
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function sofort($data)
    {
        return new SofortSource();
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return KlarnaSource
     */
    public function klarna($data)
    {
        $products = [];
        $tax = 0;
        $quote = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $lineTotal = (($item->getPrice() * $item->getQty()) - $item->getDiscountAmount() + $item->getTaxAmount());
            $price =  ($lineTotal * 100) / $item->getQty();
            $product = new Product();
            $product->name = $item->getName();
            $product->quantity = $item->getQty();
            $product->unit_price = $price;
            $product->tax_rate = $item->getTaxPercent() *100;
            $product->total_amount = $lineTotal *100;
            $product->total_tax_amount = $item->getTaxAmount() *100;

            $tax += $product->total_tax_amount;
            $products []= $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
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
        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $address = new Address();
        $address->given_name = $billingAddress->getFirstname();
        $address->family_name = $billingAddress->getLastname();
        $address->email = $billingAddress->getEmail();
        $address->street_address = $billingAddress->getStreetLine(1);
        $address->postal_code = $billingAddress->getPostcode();
        $address->city = $billingAddress->getCity();
        $address->region = $billingAddress->getRegion();
        $address->phone = $billingAddress->getTelephone();
        $address->country = strtolower($billingAddress->getCountry());

        $klarna =  new KlarnaSource(
            $data['authorization_token'],
            strtolower($billingAddress->getCountry()),
            str_replace('_', '-', $this->shopperHandler->getCustomerLocale('en_GB')),
            $address,
            $tax,
            $products
        );

        return $klarna;
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function eps($data)
    {
        return new EpsSource(
            __('Payment request from %1', $this->config->getStoreName())->getText()
        );
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function fawry($data)
    {
        $products = [];
        $quote = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $lineTotal = (($item->getPrice() * $item->getQty()) - $item->getDiscountAmount() + $item->getTaxAmount());
            $price =  ($lineTotal * 100) / $item->getQty();
            $product = new Product();
            $product->description = $item->getName();
            $product->quantity = $item->getQty();
            $product->price = $price;
            $product->product_id = $item->getId();
            $products []= $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription() && $shipping->getShippingInclTax() > 0) {
            $product = new Product();
            $product->description = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->price = $shipping->getShippingInclTax() *100;
            $product->product_id = 0;

            $products[] = $product;
        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $email = $billingAddress->getEmail();
        $phone = $billingAddress->getTelephone();
        $description = __('Payment request from %1', $this->config->getStoreName())->getText();

        return new FawrySource($email, $phone, $description, $products);
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function knet($data)
    {
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('en_GB'));
        return new KnetSource($locale[0]);
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    public function bancontact($data)
    {
        $billingAddress = $this->quoteHandler->getBillingAddress();

        $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $country = $billingAddress->getCountry();
        $desciptor = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->getText();

        return new BancontactSource($name, $country, $desciptor);
    }

    /**
     * Perform a capture request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     * @param float $amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canCapture()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the void request
            $response = $api->captureOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a refund request.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     * @param float $amount The amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);
            
            // Check the status
            if (!$this->canRefund()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            $response = $api->refundOrder($payment, $amount);

            if (!$api->isValidResponse($response)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $enabled = false;

        // Get the list of enabled apms.
        $apmEnabled = explode(
            ',',
            $this->config->getValue('apm_enabled', 'checkoutcom_apm')
        );

        $apms = $this->config->getApms();
        $billingAddress = $this->quoteHandler->getBillingAddress()->getData();

        if (isset($billingAddress['country_id'])) {
            foreach ($apms as $apm) {
                if ($this->display->isValidApm($apm, $apmEnabled, $billingAddress)) {
                    $enabled = true;
                }
            }
        }
        if (parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
            && count($this->config->getApms()) > 0
            && !$this->backendAuthSession->isLoggedIn()
            && $enabled;
        }

        return false;
    }

    /**
     * Safely get value from a multidimentional array.
     *
     * @param array $array The value
     *
     * @return Payment
     */
    public function getValue($field, $array, $dft = null)
    {
        $value = null;
        $field = (array) $field;

        foreach ($field as $key) {
            if (isset($array[$key])) {
                $value = $array[$key];
                $array = $array[$key];
            } else {
                $value = $dft;
                break;
            }
        }

        return $value;
    }
}
