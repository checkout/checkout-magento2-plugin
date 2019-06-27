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

/**
 * Class AlternativePaymentMethod
 */
class AlternativePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    const CODE = 'checkoutcom_apm';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCancel = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var ShopperHandlerService
     */
    protected $shopperHandler;

    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var Logger
     */
    protected $ckoLogger;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Session
     */
    protected $backendAuthSession;

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
        \Magento\Checkout\Model\Cart $cart,
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
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\shopperHandlerService $shopperHandler,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Logger $ckoLogger,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
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
        $this->cart               = $cart;
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
        $this->remoteAddress      = $remoteAddress;
        $this->config             = $config;
        $this->shopperHandler     = $shopperHandler;
        $this->apiHandler         = $apiHandler;
        $this->ckoLogger          = $ckoLogger;
        $this->quoteHandler       = $quoteHandler;
        $this->curl               = $curl;
    }

    /**
     * Send a charge request.
     */
    public function sendPaymentRequest(array $data, $amount, $currency, $reference = '')
    {

        try {
            $method = $data['source'];
            $response = null;

            if ($this->validateCurrency($method, $currency)) {
                // Create source object
                $source = $this->{$method}($data);
                $payment = $this->createPayment(
                    $source,
                    $amount,
                    $currency,
                    $reference,
                    $this->_code
                );

                // Send the charge request
                $response = $this->apiHandler
                                 ->checkoutApi
                                 ->payments()->request($payment);

                return $response;
            }

            return $response;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create a payment object based on the body.
     *
     * @param array $array The value
     *
     * @return Payment
     */
    protected function createPayment(IdSource $source, int $amount, string $currency, string $reference, $methodId)
    {
        try {
            $payment = null;

            // Create payment object
            $payment = new Payment($source, $currency);

            // Prepare the metadata array
            $payment->metadata = ['methodId' => $methodId];

            // Set the payment specifications
            $payment->capture = $this->config->needsAutoCapture($this->_code);
            $payment->amount = $amount * 100;
            $payment->reference = $reference;
            $payment->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
            $payment->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';

            $payment->description = __(
                'Payment request from %1',
                $this->config->getStoreName()
            );
            $payment->payment_ip = $this->remoteAddress->getRemoteAddress();
            $payment->payment_type = 'Regular';

            return $payment;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Verify if currency is supported.
     *
     * @param string $method   The method
     * @param string $currency The currency
     *
     * @return bool
     */
    protected function validateCurrency(string $method, string $currency)
    {
        try {
            $apms = $this->config->getApms();
            $valid = false;
            foreach ($apms as $apm) {
                if ($apm['value'] === $method) {
                    $valid = strpos($apm['currencies'], $currency) !== false;
                }
            }

            return $valid;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return false;
        }
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
    protected function sepa($data)
    {
        try {
            $mandate = $this->activateMandate($data['url']);
            $pos = strripos($data['url'], '/');
            $id = substr($data['url'], $pos +1);

            return new IdSource($id);
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Activate the mandate.
     *
     * @param  string $url
     * @return array
     */
    protected function activateMandate(string $url)
    {

        try {
            // Get the secret key
            $secret = $this->config->getValue('secret_key');

            // Prepare the options
            // Set the CURL headers
            $this->curl->setHeaders([
                'Content-type: ' . HttpHandler::MIME_TYPE_JSON,
                'Accept: ' . HttpHandler::MIME_TYPE_JSON,
                'Authorization: ' . $secret,
                'User-Agent: checkout-magento2-plugin/1.0.0'
            ]);

            // Set extra CURL parameters
            $this->curl->curlOption(CURLOPT_FAILONERROR, false);
            $this->curl->curlOption(CURLOPT_RETURNTRANSFER, true);

            // Send the request
            $this->curl->post($url, []);

            // Get the response
            $content = $this->curl->getBody();

            // Return the content
            return json_decode($content, true);
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    protected function alipay($data)
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
    protected function boleto($data)
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
    protected function giropay(array $data)
    {
        $source = new GiropaySource(
            __('Payment request from %1', $this->config->getStoreName()),
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
    protected function ideal($data)
    {
        $source = new IdealSource(
            $data['bic'],
            __('Payment request from %1', $this->config->getStoreName())
        );
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('nl'));
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
    protected function poli($data)
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
    protected function sofort($data)
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
    protected function klarna($data)
    {
        try {
            $products = [];
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
            //$address->title = $billingAddress->getPrefix();
            $address->street_address = $billingAddress->getStreetLine(1);
            //$address->street_address2 = $billingAddress->getStreetLine(2);
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
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    protected function eps($data)
    {
        return new EpsSource(__('Payment request from %1', $this->config->getStoreName()));
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    protected function fawry($data)
    {

        $products = [];
        $quote = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = new Product();
            $product->description = $item->getName();
            $product->quantity = $item->getQty();
            $product->price = $item->getPriceInclTax() *100;
            $product->product_id = $item->getId();
            $products []= $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product = new Product();
            $product->description = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->price = $shipping->getShippingInclTax() *100;
            $product->product_id = 0;

            $products []= $product;
        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $email = $billingAddress->getEmail();
        $phone = $billingAddress->getTelephone();
        $description = __('Payment request from %1', $this->config->getStoreName());

        return new FawrySource($email, $phone, $description, $products);
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    protected function knet($data)
    {

        $locale = explode('_', $this->shopperHandler->getCustomerLocale('en'));
        return new KnetSource($locale[0]);
    }

    /**
     * Create source.
     *
     * @param $source  The source
     *
     * @return TokenSource
     */
    protected function bancontact($data)
    {

        $billingAddress = $this->quoteHandler->getBillingAddress();

        $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $country = $billingAddress->getCountry();
        $desciptor = __(
            'Payment request from %1',
            $this->config->getStoreName()
        );

        return new BancontactSource($name, $country, $desciptor);
        ;
    }

    /**
     * Magento
     */

    /**
     * { function_description }
     *
     * @param \Magento\Payment\Model\InfoInterface $payment The payment
     *
     * @throws \Magento\Framework\Exception\LocalizedException  (description)
     *
     * @return self
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Check the status
                if (!$this->canVoid()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void action is not available.')
                    );
                }

                // Process the void request
                $response = $this->apiHandler->voidOrder($payment);
                if (!$this->apiHandler->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The void request could not be processed.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
        } finally {
            return $this;
        }
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if ($this->backendAuthSession->isLoggedIn()) {
                // Check the status
                if (!$this->canRefund()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund action is not available.')
                    );
                }

                // Process the refund request
                $response = $this->apiHandler->refundOrder($payment, $amount);
                if (!$this->apiHandler->isValidResponse($response)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The refund request could not be processed.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
        } finally {
            return $this;
        }
    }

    /**
     * Check whether method is available
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        try {
            if (parent::isAvailable($quote) && null !== $quote) {
                return $this->config->getValue('active', $this->_code)
                && count($this->config->getApms()) > 0
                && !$this->backendAuthSession->isLoggedIn();
            }

            return false;
        } catch (\Exception $e) {
            $this->ckoLogger->write($e->getMessage());
            return false;
        }
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
