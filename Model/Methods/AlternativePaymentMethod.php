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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Library\HttpHandler;
use Checkout\Models\Address;
use Checkout\Models\Payments\AlipaySource;
use Checkout\Models\Payments\BancontactSource;
use Checkout\Models\Payments\BoletoSource;
use Checkout\Models\Payments\EpsSource;
use Checkout\Models\Payments\FawrySource;
use Checkout\Models\Payments\GiropaySource;
use Checkout\Models\Payments\IdealSource;
use Checkout\Models\Payments\IdSource;
use Checkout\Models\Payments\KlarnaSource;
use Checkout\Models\Payments\KnetSource;
use Checkout\Models\Payments\Payer;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\PaypalSource;
use Checkout\Models\Payments\PoliSource;
use Checkout\Models\Payments\SofortSource;
use Checkout\Models\Payments\Source;
use Checkout\Models\Product;
use CheckoutCom\Magento2\Controller\Apm\Display;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\shopperHandlerService;
use CheckoutCom\Magento2\Model\Service\VersionHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\Session\Quote;
use Magento\Checkout\Helper\Data as CheckoutHelperData;
use Magento\Checkout\Model\Session as CheckoutModelSession;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AlternativePaymentMethod
 */
class AlternativePaymentMethod extends AbstractMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    const CODE = 'checkoutcom_apm';
    /**
     * $_code field
     *
     * @var string $_code
     */
    protected $_code = self::CODE;
    /**
     * $_canAuthorize field
     *
     * @var bool $_canAuthorize
     */
    protected $_canAuthorize = true;
    /**
     * $_canCapture field
     *
     * @var bool $_canCapture
     */
    protected $_canCapture = true;
    /**
     * $_canCapturePartial field
     *
     * @var bool $_canCapturePartial
     */
    protected $_canCapturePartial = true;
    /**
     * $_canVoid field
     *
     * @var bool $_canVoid
     */
    protected $_canVoid = true;
    /**
     * $_canUseInternal field
     *
     * @var bool $_canUseInternal
     */
    protected $_canUseInternal = false;
    /**
     * $_canUseCheckout field
     *
     * @var bool $_canUseCheckout
     */
    protected $_canUseCheckout = true;
    /**
     * $_canRefund field
     *
     * @var bool $_canRefund
     */
    protected $_canRefund = true;
    /**
     * $_canRefundInvoicePartial field
     *
     * @var bool $_canRefundInvoicePartial
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * $shopperHandler field
     *
     * @var ShopperHandlerService $shopperHandler
     */
    private $shopperHandler;
    /**
     * $apiHandler field
     *
     * @var ApiHandlerService $apiHandler
     */
    private $apiHandler;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $ckoLogger field
     *
     * @var Logger $ckoLogger
     */
    private $ckoLogger;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $curl field
     *
     * @var Curl $curl
     */
    private $curl;
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    private $backendAuthSession;
    /**
     * $versionHandler field
     *
     * @var VersionHandler $versionHandler
     */
    private $versionHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $urlBuilder field
     *
     * @var UrlInterface $urlBuilder
     */
    private $urlBuilder;
    /**
     * $_objectManager field
     *
     * @var ObjectManagerInterface $_objectManager
     */
    private $_objectManager;
    /**
     * $invoiceSender field
     *
     * @var InvoiceSender $invoiceSender
     */
    private $invoiceSender;
    /**
     * $transactionFactory field
     *
     * @var TransactionFactory $transactionFactory
     */
    private $transactionFactory;
    /**
     * $display field
     *
     * @var Display $display
     */
    private $display;
    /**
     * $customerSession field
     *
     * @var CustomerModelSession $customerSession
     */
    private $customerSession;
    /**
     * $checkoutSession field
     *
     * @var CheckoutModelSession $checkoutSession
     */
    private $checkoutSession;
    /**
     * $checkoutData field
     *
     * @var CheckoutHelperData $checkoutData
     */
    private $checkoutData;
    /**
     * $quoteRepository field
     *
     * @var CartRepositoryInterface $quoteRepository
     */
    private $quoteRepository;
    /**
     * $quoteManagement field
     *
     * @var CartManagementInterface $quoteManagement
     */
    private $quoteManagement;
    /**
     * $orderSender field
     *
     * @var OrderSender $orderSender
     */
    private $orderSender;
    /**
     * $sessionQuote field
     *
     * @var Quote $sessionQuote
     */
    private $sessionQuote;

    /**
     * AlternativePaymentMethod constructor
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param Session                    $backendAuthSession
     * @param UrlInterface               $urlBuilder
     * @param ObjectManagerInterface     $objectManager
     * @param InvoiceSender              $invoiceSender
     * @param TransactionFactory         $transactionFactory
     * @param CustomerModelSession       $customerSession
     * @param CheckoutModelSession       $checkoutSession
     * @param CheckoutHelperData         $checkoutData
     * @param CartRepositoryInterface    $quoteRepository
     * @param CartManagementInterface    $quoteManagement
     * @param OrderSender                $orderSender
     * @param Quote                      $sessionQuote
     * @param Config                     $config
     * @param shopperHandlerService      $shopperHandler
     * @param ApiHandlerService          $apiHandler
     * @param QuoteHandlerService        $quoteHandler
     * @param LoggerHelper               $ckoLogger
     * @param Utilities                  $utilities
     * @param VersionHandlerService      $versionHandler
     * @param Display                    $display
     * @param StoreManagerInterface      $storeManager
     * @param Curl                       $curl
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     * @param DirectoryHelper            $directoryHelper
     * @param DataObjectFactory          $dataObjectFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Session $backendAuthSession,
        UrlInterface $urlBuilder,
        ObjectManagerInterface $objectManager,
        InvoiceSender $invoiceSender,
        TransactionFactory $transactionFactory,
        CustomerModelSession $customerSession,
        CheckoutModelSession $checkoutSession,
        CheckoutHelperData $checkoutData,
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $quoteManagement,
        OrderSender $orderSender,
        Quote $sessionQuote,
        Config $config,
        shopperHandlerService $shopperHandler,
        ApiHandlerService $apiHandler,
        QuoteHandlerService $quoteHandler,
        LoggerHelper $ckoLogger,
        Utilities $utilities,
        VersionHandlerService $versionHandler,
        Display $display,
        StoreManagerInterface $storeManager,
        Curl $curl,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $directoryHelper,
            $dataObjectFactory,
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
     * Send a charge request
     *
     * @param string[] $data
     * @param float    $amount
     * @param string   $currency
     * @param string   $reference
     *
     * @return mixed|null
     * @throws NoSuchEntityException|LocalizedException
     */
    public function sendPaymentRequest(array $data, float $amount, string $currency, string $reference = ''): ?Payment
    {
        $method   = $data['source'];
        $response = null;

        if ($this->validateCurrency($method, $currency)) {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Create source object
            $source  = $this->{$method}($data, $reference);
            $payment = $this->createPayment(
                $source,
                $amount,
                $currency,
                $reference,
                $this->_code,
                $method
            );

            $this->ckoLogger->additional($this->utilities->objectToArray($payment), 'payment');

            // Send the charge request
            try {
                return $api->getCheckoutApi()->payments()->request($payment);
            } catch (CheckoutHttpException $e) {
                $this->ckoLogger->write($e->getBody());
            }
        }

        return null;
    }

    /**
     * Creates a payment object
     *
     * @param Source $source
     * @param float  $amount
     * @param string $currency
     * @param string $reference
     * @param string $methodId
     * @param string $method
     *
     * @return Payment
     * @throws NoSuchEntityException|LocalizedException
     */
    public function createPayment(
        Source $source,
        float  $amount,
        string $currency,
        string $reference,
        string $methodId,
        string $method
    ): Payment {
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
        $payment->capture      = $this->config->needsAutoCapture($this->_code);
        $payment->amount       = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $payment->reference    = $reference;
        $payment->success_url  = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $payment->failure_url  = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
        $payment->customer     = $this->apiHandler->createCustomer($quote);
        $payment->shipping     = $this->apiHandler->createShippingAddress($quote);
        $payment->description  = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->render();
        $payment->payment_type = 'Regular';

        return $payment;
    }

    /**
     * Verify if currency is supported
     *
     * @param string $method
     * @param string $currency
     *
     * @return bool
     */
    public function validateCurrency(string $method, string $currency): bool
    {
        $apms  = $this->config->getApms();
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
     * Create source
     *
     * @param mixed[] $data
     *
     * @return IdSource
     * @throws FileSystemException
     */
    public function sepa(array $data): IdSource
    {
        $mandate = $this->activateMandate($data['url']);
        $pos     = strripos($data['url'], '/');
        $id      = substr($data['url'], $pos + 1);

        return new IdSource($id);
    }

    /**
     * Activate the mandate.
     *
     * @param string $url
     *
     * @return mixed[]
     * @throws FileSystemException
     */
    public function activateMandate(string $url): ?array
    {
        // Get the secret key
        $secret = $this->config->getValue('secret_key');

        // Prepare the options
        // Set the CURL headers
        $this->curl->setHeaders([
            'Content-type: ' . HttpHandler::MIME_TYPE_JSON,
            'Accept: ' . HttpHandler::MIME_TYPE_JSON,
            'Authorization: ' . $secret,
            'User-Agent: checkout-magento2-plugin/' . $this->versionHandler->getModuleVersion(),
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
     * Create source
     *
     * @return AlipaySource
     */
    public function alipay(): AlipaySource
    {
        return new AlipaySource();
    }

    /**
     * Create source
     *
     * @param mixed[] $data
     *
     * @return BoletoSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function boleto(array $data): BoletoSource
    {
        $country = $this->quoteHandler->getBillingAddress()->getCountry();
        $payer   = new Payer($data['name'], $data['email'], $data['document']);

        return new BoletoSource('redirect', $country, $payer, 'Test Description');
    }

    /**
     * Create source
     *
     * @param mixed[] $data
     *
     * @return GiropaySource
     * @throws NoSuchEntityException
     */
    public function giropay(array $data): GiropaySource
    {
        /** @var string $purpose */
        $purpose = substr(
            __('Pay. req. from %1', $this->config->getStoreName())->render(),
            0,
            27
        );

        $source       = new GiropaySource(
            $purpose, $this->getValue('bic', $data)
        );
        $source->iban = $this->getValue('iban', $data);

        return $source;
    }

    /**
     * Create source
     *
     * @param mixed[] $data
     *
     * @return IdealSource
     */
    public function ideal(array $data): IdealSource
    {
        $source           = new IdealSource(
            $data['bic'], $data['description']
        );
        $locale           = explode('_', $this->shopperHandler->getCustomerLocale('nl_NL'));
        $source->language = $locale[0];

        return $source;
    }

    /**
     * Create source
     *
     * @param mixed $reference
     *
     * @return PaypalSource
     */
    public function paypal($data, $reference): PaypalSource
    {
        return new PaypalSource($reference);
    }

    /**
     * Create source
     *
     * @return PoliSource
     */
    public function poli(): PoliSource
    {
        return new PoliSource();
    }

    /**
     * Create source
     *
     * @return SofortSource
     */
    public function sofort(): SofortSource
    {
        return new SofortSource();
    }

    /**
     * Create source
     *
     * @param mixed[] $data
     *
     * @return KlarnaSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function klarna(array $data): KlarnaSource
    {
        $products = [];
        $tax      = 0;
        $quote    = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $product                   = new Product();
            $product->name             = $item->getName();
            $product->quantity         = $item->getQty();
            $product->unit_price       = $item->getPriceInclTax() * 100;
            $product->tax_rate         = $item->getTaxPercent() * 100;
            $product->total_amount     = $item->getRowTotalInclTax() * 100;
            $product->total_tax_amount = $item->getTaxAmount() * 100;

            $tax         += $product->total_tax_amount;
            $products [] = $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product                   = new Product();
            $product->name             = $shipping->getShippingDescription();
            $product->quantity         = 1;
            $product->unit_price       = $shipping->getShippingInclTax() * 100;
            $product->tax_rate         = $shipping->getTaxPercent() * 100;
            $product->total_amount     = $shipping->getShippingAmount() * 100;
            $product->total_tax_amount = $shipping->getTaxAmount() * 100;
            $product->type             = 'shipping_fee';

            $tax         += $product->total_tax_amount;
            $products [] = $product;
        }

        /* Billing */
        $billingAddress          = $this->quoteHandler->getBillingAddress();
        $address                 = new Address();
        $address->given_name     = $billingAddress->getFirstname();
        $address->family_name    = $billingAddress->getLastname();
        $address->email          = $billingAddress->getEmail();
        $address->street_address = $billingAddress->getStreetLine(1);
        $address->postal_code    = $billingAddress->getPostcode();
        $address->city           = $billingAddress->getCity();
        $address->region         = $billingAddress->getRegion();
        $address->phone          = $billingAddress->getTelephone();
        $address->country        = strtolower($billingAddress->getCountry());

        $klarna = new KlarnaSource(
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
     * Create source
     *
     * @return EpsSource
     * @throws NoSuchEntityException
     */
    public function eps(): EpsSource
    {
        /** @var string $purpose */
        $purpose = substr(
            __('Pay. req. from %1', $this->config->getStoreName())->render(),
            0,
            27
        );

        return new EpsSource($purpose);
    }

    /**
     * Create source
     *
     * @return FawrySource
     * @throws NoSuchEntityException|LocalizedException
     */
    public function fawry(): FawrySource
    {
        $products = [];
        $quote    = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $lineTotal            = (($item->getPrice() * $item->getQty()) - $item->getDiscountAmount(
                ) + $item->getTaxAmount());
            $price                = ($lineTotal * 100) / $item->getQty();
            $product              = new Product();
            $product->description = $item->getName();
            $product->quantity    = $item->getQty();
            $product->price       = $price;
            $product->product_id  = $item->getId();
            $products []          = $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription() && $shipping->getShippingInclTax() > 0) {
            $product              = new Product();
            $product->description = $shipping->getShippingDescription();
            $product->quantity    = 1;
            $product->price       = $shipping->getShippingInclTax() * 100;
            $product->product_id  = 0;

            $products[] = $product;
        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $email          = $billingAddress->getEmail();
        $phone          = $billingAddress->getTelephone();
        $description    = __('Payment request from %1', $this->config->getStoreName())->render();

        return new FawrySource($email, $phone, $description, $products);
    }

    /**
     * Create source
     *
     * @return KnetSource
     */
    public function knet(): KnetSource
    {
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('en_GB'));

        return new KnetSource($locale[0]);
    }

    /**
     * Create source
     *
     * @return BancontactSource
     * @throws NoSuchEntityException|LocalizedException
     */
    public function bancontact(): BancontactSource
    {
        $billingAddress = $this->quoteHandler->getBillingAddress();

        $name      = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $country   = $billingAddress->getCountry();
        $desciptor = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->render();

        return new BancontactSource($name, $country, $desciptor);
    }

    /**
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|AlternativePaymentMethod
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canCapture()) {
                throw new LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the void request
            $response = $api->captureOrder($payment, $amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
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
     * @param InfoInterface $payment The payment
     *
     * @return self
     * @throws LocalizedException  (description)
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The void request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response->action_id);
        }

        return $this;
    }

    /**
     * Perform a void request on order cancel.
     *
     * @param InfoInterface $payment The payment
     *
     * @return self
     * @throws LocalizedException  (description)
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        // Klarna voids are not currently supported
        if ($this->backendAuthSession->isLoggedIn() && $payment->getAdditionalInformation('method_id') !== 'klarna') {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canVoid()) {
                throw new LocalizedException(
                    __('The void action is not available.')
                );
            }

            // Process the void request
            $response = $api->voidOrder($payment);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
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
     * @param InfoInterface $payment The payment
     * @param float         $amount The amount
     *
     * @return self
     * @throws LocalizedException  (description)
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Check the status
            if (!$this->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            $response = $api->refundOrder($payment, $amount);

            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
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
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        $enabled = false;

        /** @var string|null $apmMethods */
        $apmMethods = $this->config->getValue('apm_enabled', 'checkoutcom_apm') ?: '';
        // Get the list of enabled apms.
        $apmEnabled = explode(
            ',',
            $apmMethods
        );

        $apms           = $this->config->getApms();
        $billingAddress = $this->quoteHandler->getBillingAddress()->getData();

        if (isset($billingAddress['country_id'])) {
            foreach ($apms as $apm) {
                if ($this->display->isValidApm($apm, $apmEnabled, $billingAddress)) {
                    $enabled = true;
                }
            }
        }
        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
            && count($this->config->getApms()) > 0
            && !$this->backendAuthSession->isLoggedIn()
            && $enabled;
        }

        return false;
    }

    /**
     * Safely get value from a multidimensional array
     *
     * @param mixed   $field
     * @param mixed[] $array
     * @param null    $dft
     *
     * @return mixed|null
     */
    public function getValue($field, array $array, $dft = null)
    {
        $value = null;
        $field = (array)$field;

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
