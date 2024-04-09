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

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\Payments\Payer;
use Checkout\Payments\Previous\PaymentRequest as PreviousPaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestAlipaySource;
use Checkout\Payments\Previous\Source\Apm\RequestBoletoSource;
use Checkout\Payments\Previous\Source\Apm\RequestEpsSource as PreviousRequestEpsSource;
use Checkout\Payments\Previous\Source\Apm\RequestFawrySource as PreviousRequestFawrySource;
use Checkout\Payments\Previous\Source\Apm\RequestGiropaySource as PreviousRequestGiropaySource;
use Checkout\Payments\Request\Source\Apm\RequestGiropaySource;
use Checkout\Payments\Previous\Source\Apm\RequestIdealSource as PreviousRequestIdealSource;
use Checkout\Payments\Previous\Source\Apm\RequestKlarnaSource;
use Checkout\Payments\Previous\Source\Apm\RequestKnetSource;
use Checkout\Payments\Previous\Source\Apm\RequestPayPalSource as PreviousRequestPayPalSource;
use Checkout\Payments\Previous\Source\Apm\RequestPoliSource;
use Checkout\Payments\Previous\Source\Apm\RequestSofortSource as PreviousRequestSofortSource;
use Checkout\Payments\Previous\Source\RequestIdSource;
use Checkout\Payments\Request\PaymentRequest;
use Checkout\Payments\Request\Source\Apm\FawryProduct;
use Checkout\Payments\Request\Source\Apm\RequestAlipayPlusSource;
use Checkout\Payments\Request\Source\Apm\RequestBancontactSource;
use Checkout\Payments\Request\Source\Apm\RequestEpsSource;
use Checkout\Payments\Request\Source\Apm\RequestFawrySource;
use Checkout\Payments\Request\Source\Apm\RequestIdealSource;
use Checkout\Payments\Request\Source\Apm\RequestPayPalSource;
use Checkout\Payments\Request\Source\Apm\RequestSofortSource;
use CheckoutCom\Magento2\Controller\Apm\Display;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger as LoggerHelper;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigService;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\ShopperHandlerService;
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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Store\Model\ScopeInterface;
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
     * List of unavailable apm for NAS mode
     */
    const NAS_UNAVAILABLE_APM = [
        'alipay',
        'boleto',
        'klarna',
        'poli',
        'sepa',
        'paypal'
    ];
    /**
     * List of unavailable apm for ABC mode
     */
    const ABC_UNAVAILABLE_APM = [
        'alipay',
        'poli',
    ];
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
     * @var Json
     */
    private $json;
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
     * @var VersionHandlerService
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
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Session $backendAuthSession
     * @param UrlInterface $urlBuilder
     * @param ObjectManagerInterface $objectManager
     * @param InvoiceSender $invoiceSender
     * @param TransactionFactory $transactionFactory
     * @param CustomerModelSession $customerSession
     * @param CheckoutModelSession $checkoutSession
     * @param CheckoutHelperData $checkoutData
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $quoteManagement
     * @param OrderSender $orderSender
     * @param Quote $sessionQuote
     * @param Config $config
     * @param shopperHandlerService $shopperHandler
     * @param ApiHandlerService $apiHandler
     * @param QuoteHandlerService $quoteHandler
     * @param LoggerHelper $ckoLogger
     * @param Utilities $utilities
     * @param VersionHandlerService $versionHandler
     * @param Display $display
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param DirectoryHelper $directoryHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Json $json
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
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
        ShopperHandlerService $shopperHandler,
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
        Json $json,
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

        $this->urlBuilder = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->_objectManager = $objectManager;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutData = $checkoutData;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->sessionQuote = $sessionQuote;
        $this->config = $config;
        $this->shopperHandler = $shopperHandler;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->ckoLogger = $ckoLogger;
        $this->utilities = $utilities;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->versionHandler = $versionHandler;
        $this->display = $display;
        $this->json = $json;
    }

    /**
     * Send a charge request
     *
     * @param array $data
     * @param float $amount
     * @param string $currency
     * @param string $reference
     *
     * @return array|null
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendPaymentRequest(array $data, float $amount, string $currency, string $reference = ''): ?array
    {
        $method = $data['source'];

        if ($this->validateCurrency($method, $currency)) {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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

            $this->ckoLogger->additional($this->utilities->objectToArray($payment), 'payment');

            // Send the charge request
            return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($payment);
        }

        return null;
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
     * Creates a payment object
     *
     * @param $source
     * @param float $amount
     * @param string $currency
     * @param string $reference
     * @param string $methodId
     * @param string $method
     *
     * @return PaymentRequest|PreviousPaymentRequest
     * @throws NoSuchEntityException|LocalizedException
     */
    public function createPayment(
        $source,
        float $amount,
        string $currency,
        string $reference,
        string $methodId,
        string $method
    ) {
        // Create payment object
        if ($this->apiHandler->isPreviousMode()) {
            $payment = new PreviousPaymentRequest();
        } else {
            $payment = new PaymentRequest();
        }

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
        $payment->currency = $currency;
        $payment->source = $source;
        $payment->capture = $this->config->needsAutoCapture();
        $payment->amount = $this->quoteHandler->amountToGateway(
            $this->utilities->formatDecimals($amount),
            $quote
        );
        $payment->reference = $reference;
        $payment->success_url = $this->config->getStoreUrl() . 'checkout_com/payment/verify';
        $payment->failure_url = $this->config->getStoreUrl() . 'checkout_com/payment/fail';
        $payment->customer = $this->apiHandler->createCustomer($quote);
        $payment->shipping = $this->apiHandler->createShippingAddress($quote);
        $payment->items = $this->apiHandler->createItems($quote);
        $payment->description = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->render();
        $payment->payment_type = 'Regular';
        $payment->processing_channel_id = $this->config->getValue('channel_id');

        return $payment;
    }

    /**
     * API related.
     */

    /**
     * Safely get value from a multidimensional array
     *
     * @param mixed $field
     * @param array $array
     * @param null $dft
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

    /**
     * Create source
     *
     * @param array $data
     *
     * @return RequestIdSource
     * @throws FileSystemException
     */
    public function sepa(array $data): RequestIdSource
    {
        $this->activateMandate($data['url']);
        $pos = strripos($data['url'], '/');
        $id = substr($data['url'], $pos + 1);

        $source = new RequestIdSource();
        $source->id = $id;

        return $source;
    }

    /**
     * Activate the mandate.
     *
     * @param string $url
     *
     * @return array|null
     * @throws FileSystemException
     */
    public function activateMandate(string $url): ?array
    {
        // Get the secret key
        $secret = $this->config->getValue('secret_key');

        // Prepare the options
        // Set the CURL headers
        $this->curl->setHeaders([
            'Content-type' => 'json',
            'Accept' => 'json',
            'Authorization' => $secret,
            'User-Agent' => 'checkout-magento2-plugin/' . $this->versionHandler->getModuleVersion(),
        ]);

        // Set extra CURL parameters
        $this->curl->setOption(CURLOPT_FAILONERROR, false);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

        // Send the request
        $this->curl->get($url);

        // Get the response
        $content = $this->curl->getBody();

        // Return the content
        return $this->json->unserialize($content);
    }

    /**
     * @return RequestAlipaySource|RequestAlipayPlusSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function alipay()
    {
        if ($this->apiHandler->isPreviousMode()) {
            return new RequestAlipaySource();
        } else {
            // don't work for NAS mode for now
            return RequestAlipayPlusSource::requestAlipayPlusSource();
        }
    }

    /**
     * @param array $data
     *
     * @return RequestBoletoSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function boleto(array $data): RequestBoletoSource
    {
        $country = $this->quoteHandler->getBillingAddress()->getCountry();
        $payer = new Payer();
        $payer->document = $data['document'];
        $payer->email = $data['email'];
        $payer->name = $data['name'];

        $boletoSource = new RequestBoletoSource();
        $boletoSource->country = $country;
        $boletoSource->payer = $payer;
        $boletoSource->description = 'Test Description';

        return $boletoSource;
    }

    /**
     * Create source
     *
     * @param array $data
     *
     * @return RequestGiropaySource|PreviousRequestGiropaySource
     * @throws NoSuchEntityException
     */
    public function giropay(array $data)
    {
        if ($this->apiHandler->isPreviousMode()) {
            $source = new PreviousRequestGiropaySource();
            $source->purpose = null;
            $source->bic = $this->getValue('bic', $data);
            $source->info_fields = null;
        } else {
            $source = new RequestGiropaySource();
        }

        return $source;
    }

    /**
     * @param array $data
     *
     * @return PreviousRequestIdealSource|RequestIdealSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function ideal(array $data)
    {
        if ($this->apiHandler->isPreviousMode()) {
            $source = new PreviousRequestIdealSource();
        } else {
            $source = new RequestIdealSource();
        }
        $source->bic = $data['bic'];
        $source->description = $data['description'];
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('nl_NL') ?? '');
        $source->language = $locale[0];

        return $source;
    }

    /**
     * @param array $data
     * @param string $reference
     *
     * @return PreviousRequestPayPalSource|RequestPayPalSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function paypal(array $data, string $reference)
    {
        if ($this->apiHandler->isPreviousMode()) {
            $source = new PreviousRequestPayPalSource();
            $source->invoice_number = $reference;

            return $source;
        } else {
            return new RequestPayPalSource();
        }
    }

    /**
     * Create source
     *
     * @return RequestPoliSource
     */
    public function poli(): RequestPoliSource
    {
        return new RequestPoliSource();
    }

    /**
     * @param array $data
     *
     * @return PreviousRequestSofortSource|RequestSofortSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sofort(array $data)
    {
        if ($this->apiHandler->isPreviousMode()) {
            return new PreviousRequestSofortSource();
        } else {
            return new RequestSofortSource();
        }
    }

    /**
     * @param array $data
     *
     * @return RequestKlarnaSource
     * @throws CheckoutApiException
     */
    public function klarna(array $data): RequestKlarnaSource
    {
        $creditSession = $this->apiHandler->getCheckoutApi()->getKlarnaClient()->getCreditSession($data['session_id']);
        $source = new RequestKlarnaSource();

        if ($this->apiHandler->isValidResponse($creditSession)) {
            $source->billing_address = $creditSession['billing_address'];
            $source->authorization_token = $data['authorization_token'];
            $source->tax_amount = $creditSession['tax_amount'];
            $source->locale = $creditSession['locale'];
            $source->purchase_country = $creditSession['purchase_country'];
            $source->products = $creditSession['products'];
        }

        return $source;
    }

    /**
     * @return PreviousRequestEpsSource|RequestEpsSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function eps()
    {
        /** @var string $purpose */
        $purpose = substr(
            __('Pay. req. from %1', $this->config->getStoreName())->render(),
            0,
            27
        );

        if ($this->apiHandler->isPreviousMode()) {
            $epsSource = new PreviousRequestEpsSource();
        } else {
            $epsSource = new RequestEpsSource();
        }

        $epsSource->purpose = $purpose;

        return $epsSource;
    }

    /**
     * @return PreviousRequestFawrySource|RequestFawrySource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function fawry()
    {
        $products = [];
        $quote = $this->quoteHandler->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $unitPrice = $this->quoteHandler->amountToGateway(
                $this->utilities->formatDecimals($item->getPriceInclTax()),
                $quote
            );
            $product = new FawryProduct();
            $product->description = $item->getName();
            $product->quantity = $item->getQty();
            $product->price = $unitPrice;
            $product->product_id = $item->getId();
            $products [] = $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription() && $shipping->getShippingInclTax() > 0) {
            $product = new FawryProduct();
            $product->description = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->price = $shipping->getShippingInclTax() * 100;
            $product->product_id = 0;

            $products[] = $product;
        }

        /* Billing */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $email = $billingAddress->getEmail();
        $phone = $billingAddress->getTelephone();
        $description = __('Payment request from %1', $this->config->getStoreName())->render();

        if ($this->apiHandler->isPreviousMode()) {
            $fawrySource = new PreviousRequestFawrySource();
        } else {
            $fawrySource = new RequestFawrySource();
        }

        $fawrySource->customer_email = $email;
        $fawrySource->description = $description;
        $fawrySource->products = $products;
        $fawrySource->customer_mobile = $phone;

        return $fawrySource;
    }

    /**
     * Create source
     *
     * @return RequestKnetSource
     */
    public function knet(): RequestKnetSource
    {
        $locale = explode('_', $this->shopperHandler->getCustomerLocale('en_GB') ?? '');

        $knetSource = new RequestKnetSource();

        $knetSource->language = $locale[0];

        return $knetSource;
    }

    /**
     * @return RequestBancontactSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function bancontact(): RequestBancontactSource
    {
        $billingAddress = $this->quoteHandler->getBillingAddress();

        $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $country = $billingAddress->getCountry();
        $descriptor = __(
            'Payment request from %1',
            $this->config->getStoreName()
        )->render();

        $bancontactSource = new RequestBancontactSource();

        $bancontactSource->payment_country = $country;
        $bancontactSource->account_holder_name = $name;
        $bancontactSource->billing_descriptor = $descriptor;

        return $bancontactSource;
    }

    /**
     * Perform a capture request
     *
     * @param InfoInterface $payment
     * @param $amount
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function capture(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            // Check the status
            if (!$this->canCapture()) {
                throw new LocalizedException(
                    __('The capture action is not available.')
                );
            }

            // Process the void request
            $response = $api->captureOrder($payment, (float)$amount);
            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The capture request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * Perform a void request.
     *
     * @param InfoInterface $payment
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * Perform a void request on order cancel.
     *
     * @param InfoInterface $payment
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment): AbstractMethod
    {
        // Klarna voids are not currently supported
        if ($this->backendAuthSession->isLoggedIn() && $payment->getAdditionalInformation('method_id') !== 'klarna') {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

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
            $payment->setTransactionId($response['action_id']);
        }

        return $this;
    }

    /**
     * Perform a refund request.
     *
     * @param InfoInterface $payment
     * @param $amount
     *
     * @return AbstractMethod
     * @throws CheckoutApiException
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refund(InfoInterface $payment, $amount): AbstractMethod
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the store code
            $storeCode = $payment->getOrder()->getStore()->getCode();

            // Initialize the API handler
            try {
                $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
            } catch (CheckoutArgumentException $e) {
                if (!$this->config->isAbcRefundAfterNasMigrationActive($storeCode)) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $api = $this->apiHandler->initAbcForRefund($storeCode, ScopeInterface::SCOPE_STORE);
            }

            // Check the status
            if (!$this->canRefund()) {
                throw new LocalizedException(
                    __('The refund action is not available.')
                );
            }

            // Process the refund request
            try {
                $response = $api->refundOrder($payment, $amount);
            } catch (CheckoutApiException $e) {
                if (!$this->config->isAbcRefundAfterNasMigrationActive($storeCode)) {
                    throw new LocalizedException(__($e->getMessage()));
                }
                $api = $this->apiHandler->initAbcForRefund($storeCode, ScopeInterface::SCOPE_STORE);
                $response = $api->refundOrder($payment, $amount);
            }

            if (!$api->isValidResponse($response)) {
                throw new LocalizedException(
                    __('The refund request could not be processed.')
                );
            }

            // Set the transaction id from response
            $payment->setTransactionId($response['action_id']);
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
        $countEnabled = 0;
        $websiteId = $this->storeManager->getWebsite()->getId();
        $service = $this->scopeConfig->getValue(ConfigService::SERVICE_CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE, $websiteId);

        /** @var string|null $apmMethods */
        $apmMethods = $this->config->getValue('apm_enabled', 'checkoutcom_apm') ?: '';
        // Get the list of enabled apms.
        $apmEnabled = explode(
            ',',
            $apmMethods ?? ''
        );

        $apms = $this->config->getApms();
        $billingAddress = $this->quoteHandler->getBillingAddress()->getData();

        if (isset($billingAddress['country_id'])) {
            foreach ($apms as $apm) {
                if ($this->display->isValidApm($apm, $apmEnabled, $billingAddress)) {
                    if ((($service === ConfigService::SERVICE_NAS) && !in_array($apm['value'], self::NAS_UNAVAILABLE_APM))
                        || ($this->apiHandler->isPreviousMode() && !in_array($apm['value'], self::ABC_UNAVAILABLE_APM))) {
                        $countEnabled++;
                    }
                }
            }
        }

        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->_code)
                   && count($this->config->getApms()) > 0
                   && !$this->backendAuthSession->isLoggedIn()
                   && $countEnabled > 0;
        }

        return false;
    }
}
