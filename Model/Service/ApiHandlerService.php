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

namespace CheckoutCom\Magento2\Model\Service;

use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\CheckoutSdk;
use Checkout\CheckoutUtils;
use Checkout\Common\Address;
use Checkout\Common\CustomerRequest;
use Checkout\HttpMetadata;
use Checkout\Payments\CaptureRequest;
use Checkout\Payments\Previous\CaptureRequest as PreviousCaptureRequest;
use Checkout\Payments\Product;
use Checkout\Payments\RefundRequest;
use Checkout\Payments\ShippingDetails;
use Checkout\Payments\VoidRequest;
use Checkout\Previous\CheckoutApi as PreviousCheckoutApi;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ApiHandlerService
 */
class ApiHandlerService
{
    /**
     * Valid return code
     */
    public const VALID_RESPONSE_CODE = [200, 201, 202];
    /**
     * @var mixed
     */
    protected $checkoutApi;
    /**
     * @var Json
     */
    protected $json;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $productMeta field
     *
     * @var ProductMetadataInterface $productMeta
     */
    private $productMeta;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    private $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $versionHandler field
     *
     * @var VersionHandlerService $versionHandler
     */
    private $versionHandler;

    /**
     * ApiHandlerService constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMeta
     * @param Config $config
     * @param Utilities $utilities
     * @param Logger $logger
     * @param OrderHandlerService $orderHandler
     * @param QuoteHandlerService $quoteHandler
     * @param VersionHandlerService $versionHandler
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMeta,
        Config $config,
        Utilities $utilities,
        Logger $logger,
        OrderHandlerService $orderHandler,
        QuoteHandlerService $quoteHandler,
        VersionHandlerService $versionHandler,
        ScopeConfigInterface $scopeConfig,
        Json $json
    ) {
        $this->storeManager = $storeManager;
        $this->productMeta = $productMeta;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->orderHandler = $orderHandler;
        $this->quoteHandler = $quoteHandler;
        $this->versionHandler = $versionHandler;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Load the API client
     *
     * @param string|int|null $storeCode
     * @param string $scope
     * @param string|null $secretKey
     * @param string|null $publicKey
     *
     * @return $this
     * @throws CheckoutArgumentException
     */
    public function init(
        $storeCode = null,
        string $scope = ScopeInterface::SCOPE_WEBSITE,
        string $secretKey = null,
        string $publicKey = null
    ): ApiHandlerService {
        if (!$secretKey) {
            $secretKey = $this->config->getValue('secret_key', null, $storeCode, $scope);
        }

        if (!$publicKey) {
            $publicKey = $this->config->getValue('public_key', null, $storeCode, $scope);
        }

        $service = $this->scopeConfig->getValue(ConfigService::SERVICE_CONFIG_PATH, $scope, $storeCode);
        $environment = $this->config->getEnvironment((int)$storeCode, $scope);
        $api = CheckoutSdk::builder();

        if ($service === ConfigService::SERVICE_ABC) {
            $api = $api->previous()->staticKeys();
        } else {
            $api = $api->staticKeys();
        }

        $this->checkoutApi = $api
            ->publicKey($publicKey)
            ->secretKey($secretKey)
            ->environment($environment)
            ->build();

        return $this;
    }

    public function initAbcForRefund(
        $storeCode = null,
        string $scope = ScopeInterface::SCOPE_WEBSITE
    ): ApiHandlerService
    {
        $secretKey = $this->config->getValue('abc_refund_secret_key', null, $storeCode, $scope);
        $publicKey = $this->config->getValue('abc_refund_public_key', null, $storeCode, $scope);

        $api = CheckoutSdk::builder();
        $environment = $this->config->getEnvironment((int)$storeCode, $scope);

        $this->checkoutApi = $api
            ->previous()->staticKeys()
            ->publicKey($publicKey)
            ->secretKey($secretKey)
            ->environment($environment)
            ->build();

        return $this;
    }

    /**
     * Checks if a response is valid
     *
     * @param array $response
     *
     * @return bool
     */
    public function isValidResponse(array $response): bool
    {
        if (!isset($response['http_metadata'])) {
            return false;
        }

        $response = $response['http_metadata'];

        $this->logger->additional($this->utilities->objectToArray($response), 'api');

        return $response instanceof HttpMetadata && in_array($response->getStatusCode(), self::VALID_RESPONSE_CODE);
    }

    /**
     * Captures a transaction
     *
     * @param $payment
     * @param float $amount
     *
     * @return array|void
     * @throws CheckoutApiException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function captureOrder($payment, float $amount)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the capture request
        if (isset($paymentInfo['id'])) {
            // Prepare the request
            if ($this->isPreviousMode()) {
                $request = new PreviousCaptureRequest();
            } else {
                $request = new CaptureRequest();
            }

            $request->amount = $this->orderHandler->amountToGateway(
                $this->utilities->formatDecimals($amount * $order->getBaseToOrderRate()),
                $order
            );

            // Get the response
            $response = $this->getCheckoutApi()->getPaymentsClient()->capturePayment($paymentInfo['id'], $request);

            // Logging
            $this->logger->display($response);

            return $response;
        }
    }

    /**
     * Get CheckoutApi
     *
     * @return CheckoutApi|PreviousCheckoutApi
     */
    public function getCheckoutApi()
    {
        return $this->checkoutApi;
    }

    /**
     * Voids a transaction
     *
     * @param $payment
     *
     * @return array|void
     * @throws CheckoutApiException
     * @throws LocalizedException
     */
    public function voidOrder($payment)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the void request
        if (isset($paymentInfo['id'])) {
            $request = new VoidRequest();
            $request->reference = $paymentInfo['id'];
            $response = $this->getCheckoutApi()->getPaymentsClient()->voidPayment($paymentInfo['id'], $request);

            // Logging
            $this->logger->display($response);

            return $response;
        }
    }

    /**
     * Refunds a transaction
     *
     * @param $payment
     * @param $amount
     *
     * @return array|void
     * @throws CheckoutApiException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refundOrder($payment, $amount)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the refund request
        if (isset($paymentInfo['id'])) {
            $request = new RefundRequest();
            $request->amount = $this->orderHandler->amountToGateway(
                $this->utilities->formatDecimals($amount * $order->getBaseToOrderRate()),
                $order
            );

            // Get the response
            $response = $this->getCheckoutApi()->getPaymentsClient()->refundPayment($paymentInfo['id'], $request);

            // Logging
            $this->logger->display($response);

            // Return the response
            return $response;
        }
    }

    /**
     * Gets payment details
     *
     * @param string $paymentId
     *
     * @return array
     * @throws CheckoutApiException
     */
    public function getPaymentDetails(string $paymentId): array
    {
        return $this->getCheckoutApi()->getPaymentsClient()->getPaymentDetails($paymentId);
    }

    /**
     * Creates a customer source
     *
     * @param $entity
     *
     * @return CustomerRequest
     */
    public function createCustomer($entity): CustomerRequest
    {
        // Get the billing address
        $billingAddress = $entity->getBillingAddress();

        // Create the customer
        $customer = new CustomerRequest();
        $customer->email = $billingAddress->getEmail();
        $customer->name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

        return $customer;
    }

    /**
     * @param $entity
     *
     * @return Address
     */
    public function createBillingAddress($entity): Address
    {
        // Get the billing address
        /** @var CartInterface $entity */
        $billingAddress = $entity->getBillingAddress();

        // Create the address
        $address = new Address();
        $address->address_line1 = $billingAddress->getStreetLine(1);
        $address->address_line2 = $billingAddress->getStreetLine(2);
        $address->city = $billingAddress->getCity();
        $address->zip = $billingAddress->getPostcode();
        $address->country = $billingAddress->getCountry();
        $address->state = $billingAddress->getRegion();

        return $address;
    }

    /**
     * Creates a shipping address
     *
     * @param $entity
     *
     * @return ShippingDetails
     */
    public function createShippingAddress($entity): ShippingDetails
    {
        // Get the billing address
        $shippingAddress = $entity->getShippingAddress();

        // Create the address
        $address = new Address();
        $address->address_line1 = $shippingAddress->getStreetLine(1);
        $address->address_line2 = $shippingAddress->getStreetLine(2);
        $address->city = $shippingAddress->getCity();
        $address->zip = $shippingAddress->getPostcode();
        $address->country = $shippingAddress->getCountry();
        $address->state = $shippingAddress->getRegion();

        $shippingDetails = new ShippingDetails();
        $shippingDetails->address = $address;

        return $shippingDetails;
    }

    /**
     * @param CartInterface $entity
     *
     * @return Product[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createItems(CartInterface $entity): array
    {
        /** @var Product[] $items */
        $items = [];
        /** @var CartItemInterface $item */
        foreach ($entity->getItems() as $item) {
            $product = new Product();
            /** @var float|int|mixed $unitPrice */
            $unitPrice = $this->quoteHandler->amountToGateway(
                $this->utilities->formatDecimals($item->getPriceInclTax()),
                $entity
            );

            $product->name = $item->getName();
            $product->unit_price = $unitPrice;
            $product->quantity = $item->getQty();

            $items[] = $product;
        }

        // Shipping fee
        $shipping = $entity->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product = new Product();
            $product->name = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->unit_price = $shipping->getShippingInclTax() * 100;
            $product->tax_rate = $shipping->getTaxPercent() * 100;
            $product->total_amount = $shipping->getShippingAmount() * 100;
            $product->total_tax_amount = $shipping->getTaxAmount() * 100;
            $product->type = 'shipping_fee';

            $items[] = $product;
        }

        return $items;
    }

    /**
     * Get base metadata
     *
     * @return array
     * @throws NoSuchEntityException|FileSystemException
     */
    public function getBaseMetadata(): array
    {
        // Get the website URL
        $serverUrl = $this->storeManager->getStore()->getBaseUrl();

        // Get the SDK data
        $sdkData = 'PHP v' . phpversion() . ', SDK v' . CheckoutUtils::PROJECT_VERSION;

        // Get the integration data
        $integrationData = 'Checkout.com Magento 2 Module ';
        $integrationData .= $this->versionHandler->getModuleVersion('v');

        // Get the Magento version
        $platformData = 'Magento ' . $this->productMeta->getVersion();

        return [
            'udf5' => $this->json->serialize([
                'server_url' => $serverUrl,
                'sdk_data' => $sdkData,
                'integration_data' => $integrationData,
                'platform_data' => $platformData,
            ]),
        ];
    }

    /**
     * @return bool
     * @throws NoSuchEntityException|LocalizedException
     */
    public function isPreviousMode(): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        $service = $this->scopeConfig->getValue(ConfigService::SERVICE_CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeCode);

        return $service === ConfigService::SERVICE_ABC;
    }
}
