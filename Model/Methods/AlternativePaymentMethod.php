<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Methods;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\EnvironmentSubdomainFactory;
use Checkout\Payments\Payer;
use Checkout\Payments\Previous\PaymentRequest as PreviousPaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestAlipaySource;
use Checkout\Payments\Previous\Source\Apm\RequestBoletoSource;
use Checkout\Payments\Previous\Source\Apm\RequestEpsSource as PreviousRequestEpsSource;
use Checkout\Payments\Previous\Source\Apm\RequestFawrySource as PreviousRequestFawrySource;
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
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OnlineCaptureGuard;
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
     * $code field
     *
     * @var string $code
     */
    protected $code = self::CODE;
    /**
     * $canAuthorize field
     *
     * @var bool $canAuthorize
     */
    protected $canAuthorize = true;
    /**
     * $canCapture field
     */
    protected $canCapture = true;
    /**
     * $canCapturePartial field
     */
    protected $canCapturePartial = true;
    /**
     * $canVoid field
     */
    protected $canVoid = true;
    /**
     * $canUseInternal field
     */
    protected $canUseInternal = false;
    /**
     * $canUseCheckout field
     *
     * @var bool $canUseCheckout
     */
    protected $canUseCheckout = true;
    /**
     * $canRefund field
     *
     * @var bool $canRefund
     */
    protected $canRefund = true;
    /**
     * $canRefundInvoicePartial field
     *
     * @var bool $canRefundInvoicePartial
     */
    protected $canRefundInvoicePartial = true;

    private Session $backendAuthSession;
    private OnlineCaptureGuard $onlineCaptureGuard;
    private UrlInterface $urlBuilder;
    private ObjectManagerInterface $objectManager;
    private InvoiceSender $invoiceSender;
    private TransactionFactory $transactionFactory;
    private CustomerModelSession $customerSession;
    private CheckoutModelSession $checkoutSession;
    private CheckoutHelperData $checkoutData;
    private CartRepositoryInterface $quoteRepository;
    private CartManagementInterface $quoteManagement;
    private OrderSender $orderSender;
    private Quote $sessionQuote;
    private Config $config;
    private ShopperHandlerService $shopperHandler;
    private ApiHandlerService $apiHandler;
    private QuoteHandlerService $quoteHandler;
    private LoggerHelper $ckoLogger;
    private Utilities $utilities;
    private VersionHandlerService $versionHandler;
    private Display $display;
    private StoreManagerInterface $storeManager;
    private Curl $curl;
    private Json $json;
    private EnvironmentSubdomainFactory $environmentSubdomainFactory;

    public function __construct(
        Session $backendAuthSession,
        OnlineCaptureGuard $onlineCaptureGuard,
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
        Json $json,
        EnvironmentSubdomainFactory $environmentSubdomainFactory,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        DirectoryHelper $directoryHelper,
        DataObjectFactory $dataObjectFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $directoryHelper,
            $scopeConfig,
            $logger,
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $dataObjectFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->backendAuthSession = $backendAuthSession;
        $this->onlineCaptureGuard = $onlineCaptureGuard;
        $this->urlBuilder = $urlBuilder;
        $this->objectManager = $objectManager;
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
        $this->versionHandler = $versionHandler;
        $this->display = $display;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->json = $json;
        $this->environmentSubdomainFactory = $environmentSubdomainFactory;
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
        $method = is_string($data['source'] ?? null) ? $data['source'] : '';

        // The source is client controlled: only an APM enabled in the configuration is accepted
        if (!$this->isAllowedSource($method)) {
            $this->ckoLogger->error(
                'APM payment rejected: source is not an enabled APM: '
                . json_encode(mb_substr($method, 0, 64), JSON_UNESCAPED_SLASHES)
            );

            throw new LocalizedException(__('The payment method is not available.'));
        }

        if ($this->validateCurrency($method, $currency)) {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            // Create source object
            $source = $this->createSource($method, $data, $reference);

            $payment = $this->createPayment(
                $source,
                $amount,
                $currency,
                $reference,
                $this->code,
                $method
            );

            $this->ckoLogger->additional($this->utilities->objectToArray($payment), 'payment');

            // Send the charge request
            return $api->getCheckoutApi()->getPaymentsClient()->requestPayment($payment);
        }

        return null;
    }

    /**
     * Check that the requested APM is offered on the storefront for the quote
     *
     * Same rule as the APM list displayed on the checkout (see getAvailableApms()), so that a forged
     * request cannot use an APM the shopper is not offered.
     *
     * @param string $source
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAllowedSource(string $source, ?CartInterface $quote = null): bool
    {
        return in_array($source, $this->getAvailableApms($quote), true);
    }

    /**
     * List the APM ids offered on the storefront for the quote
     *
     * Same rule as Controller\Apm\Display: enabled in the configuration, valid for the billing
     * country and the quote currency, and not part of NAS_UNAVAILABLE_APM.
     *
     * @param CartInterface|null $quote
     *
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAvailableApms(?CartInterface $quote = null): array
    {
        if (!$quote) {
            $quote = $this->quoteHandler->getQuote();
        }

        $billingAddress = $quote->getBillingAddress()->getData();
        if (!isset($billingAddress['country_id'])) {
            return [];
        }

        $apmEnabled = explode(',', (string)$this->config->getValue('apm_enabled', $this->code));
        $available = [];

        foreach ($this->config->getApms() as $apm) {
            if ($this->display->isValidApm($apm, $apmEnabled, $billingAddress, $quote)
                && !in_array($apm['value'], self::NAS_UNAVAILABLE_APM, true)
            ) {
                $available[] = $apm['value'];
            }
        }

        return $available;
    }

    /**
     * Build the payment source of an APM through an explicit mapping, never a dynamic method call
     *
     * @param string $method
     * @param array $data
     * @param string $reference
     *
     * @return mixed
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function createSource(string $method, array $data, string $reference)
    {
        switch ($method) {
            case 'alipay':
                return $this->alipay();
            case 'bancontact':
                return $this->bancontact();
            case 'boleto':
                return $this->boleto($data);
            case 'eps':
                return $this->eps();
            case 'fawry':
                return $this->fawry();
            case 'ideal':
                return $this->ideal($data);
            case 'klarna':
                return $this->klarna($data);
            case 'knet':
                return $this->knet();
            case 'paypal':
                return $this->paypal($data, $reference);
            case 'poli':
                return $this->poli();
            case 'sepa':
                return $this->sepa($data);
            case 'sofort':
                return $this->sofort($data);
            default:
                throw new LocalizedException(__('The payment method is not available.'));
        }
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
        $payment = new PaymentRequest();

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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sepa(array $data): RequestIdSource
    {
        // The posted value is client controlled: only the mandate id is kept from it, the URL called
        // with the secret key is always rebuilt from the configuration (see activateMandate())
        $postedUrl = is_string($data['url'] ?? null) ? $data['url'] : '';
        $id = substr((string)strrchr('/' . $postedUrl, '/'), 1);

        if (!$this->isValidMandateId($id)) {
            $this->ckoLogger->error(
                'SEPA mandate rejected: invalid mandate reference: '
                . json_encode(mb_substr($postedUrl, 0, 255), JSON_UNESCAPED_SLASHES)
            );

            throw new LocalizedException(__('Invalid SEPA mandate reference.'));
        }

        $this->activateMandate($id);

        $source = new RequestIdSource();
        $source->id = $id;

        return $source;
    }

    /**
     * Activate the mandate.
     *
     * The URL is always built from the configured Checkout.com environment so that the
     * secret key can never be sent to a host supplied by the shopper (SSRF).
     *
     * @param string $mandateId
     *
     * @return array|null
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function activateMandate(string $mandateId): ?array
    {
        if (!$this->isValidMandateId($mandateId)) {
            throw new LocalizedException(__('Invalid SEPA mandate reference.'));
        }

        $url = $this->buildMandateUrl($mandateId);

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
     * Check that a SEPA mandate id has the Checkout.com source id format.
     *
     * @param string $mandateId
     *
     * @return bool
     */
    private function isValidMandateId(string $mandateId): bool
    {
        return (bool)preg_match('/^src_[a-z0-9]{1,64}\z/', $mandateId);
    }

    /**
     * Build the SEPA mandate URL from the configured Checkout.com environment.
     *
     * Same base URL as the SDK client (see ApiHandlerService::init()): the merchant subdomain
     * built from the partial client id is added when it is configured.
     *
     * @param string $mandateId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function buildMandateUrl(string $mandateId): string
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        $environment = $this->config->getEnvironment($storeCode, ScopeInterface::SCOPE_STORE);
        $partialClientId = (string)$this->config->getValue(
            'partial_client_id',
            null,
            $storeCode,
            ScopeInterface::SCOPE_STORE
        );

        $baseUri = $partialClientId !== ''
            ? $this->environmentSubdomainFactory->create([
                'environment' => $environment,
                'subdomain' => strtolower($partialClientId),
            ])->getBaseUri()
            : $environment->getBaseUri();

        return rtrim($baseUri, '/') . '/sepa/mandates/' . $mandateId;
    }

    /**
     * @return RequestAlipaySource|RequestAlipayPlusSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function alipay()
    {
        // don't work for NAS mode for now
        return RequestAlipayPlusSource::requestAlipayPlusSource();
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
     * @param array $data
     *
     * @return PreviousRequestIdealSource|RequestIdealSource
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function ideal(array $data)
    {
        $source = new RequestIdealSource();
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
        return new RequestPayPalSource();
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
        return new RequestSofortSource();
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

        $epsSource = new RequestEpsSource();

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

        $fawrySource = new RequestFawrySource();

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
        if ($this->onlineCaptureGuard->canCaptureOnline()) {
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
                throw new LocalizedException(__($e->getMessage()));
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
                throw new LocalizedException(__($e->getMessage()));
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
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if (!$quote) {
            $quote = $this->quoteHandler->getQuote();
        }

        $countEnabled = count($this->getAvailableApms($quote));

        if ($this->isModuleActive() && parent::isAvailable($quote) && null !== $quote) {
            return $this->config->getValue('active', $this->code)
                && count($this->config->getApms()) > 0
                && !$this->backendAuthSession->isLoggedIn()
                && $countEnabled > 0;
        }

        return false;
    }
}
