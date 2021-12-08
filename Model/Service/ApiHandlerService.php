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

namespace CheckoutCom\Magento2\Model\Service;

use \Checkout\CheckoutApi;
use \Checkout\Models\Payments\Capture;
use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;
use \Checkout\Models\Payments\Customer;
use \Checkout\Models\Address;
use \Checkout\Models\Payments\Shipping;
use \Checkout\Models\Phone;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ApiHandlerService.
 */
class ApiHandlerService
{
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $productMeta field
     *
     * @var ProductMetadataInterface $productMeta
     */
    public $productMeta;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $checkoutApi field
     *
     * @var CheckoutApi $checkoutApi
     */
    public $checkoutApi;
    /**
     * $utilities field
     *
     * @var Utilities $utilities
     */
    public $utilities;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    public $logger;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    public $orderHandler;
    /**
     * $versionHandler field
     *
     * @var VersionHandlerService $versionHandler
     */
    public $versionHandler;

    /**
     * ApiHandlerService constructor
     *
     * @param StoreManagerInterface    $storeManager
     * @param ProductMetadataInterface $productMeta
     * @param Config                    $config
     * @param Utilities                $utilities
     * @param Logger                   $logger
     * @param OrderHandlerService      $orderHandler
     * @param VersionHandlerService    $versionHandler
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMeta,
        Config $config,
        Utilities $utilities,
        Logger $logger,
        OrderHandlerService $orderHandler,
        VersionHandlerService $versionHandler
    ) {
        $this->storeManager   = $storeManager;
        $this->productMeta    = $productMeta;
        $this->config          = $config;
        $this->utilities      = $utilities;
        $this->logger         = $logger;
        $this->orderHandler   = $orderHandler;
        $this->versionHandler = $versionHandler;
    }

    /**
     * Load the API client
     *
     * @param null   $storeCode
     * @param string $scope
     * @param null   $secretKey
     *
     * @return $this
     */
    public function init(
        $storeCode = null,
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $secretKey = null
    ) {
        if ($secretKey) {
            $this->checkoutApi = new CheckoutApi(
                $secretKey, $this->config->getValue('environment', null, $storeCode, $scope)
            );
        } else {
            $this->checkoutApi = new CheckoutApi(
                $this->config->getValue('secret_key', null, $storeCode, $scope),
                $this->config->getValue('environment', null, $storeCode, $scope),
                $this->config->getValue('public_key', null, $storeCode, $scope)
            );
        }

        return $this;
    }

    /**
     * Checks if a response is valid
     *
     * @param $response
     *
     * @return bool
     */
    public function isValidResponse($response)
    {
        $this->logger->additional($this->utilities->objectToArray($response), 'api');

        return $response != null && is_object($response) && method_exists(
                $response,
                'isSuccessful'
            ) && $response->isSuccessful();
    }

    /**
     * Captures a transaction
     *
     * @param $payment
     * @param $amount
     *
     * @return mixed|void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function captureOrder($payment, $amount)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the capture request
        if (isset($paymentInfo['id'])) {
            // Prepare the request
            $request         = new Capture($paymentInfo['id']);
            $request->amount = $this->orderHandler->amountToGateway(
                $this->utilities->formatDecimals($amount * $order->getBaseToOrderRate()),
                $order
            );

            // Get the response
            $response = $this->checkoutApi->payments()->capture($request);

            // Logging
            $this->logger->display($response);

            return $response;
        }
    }

    /**
     * Voids a transaction
     *
     * @param $payment
     *
     * @return mixed|void
     */
    public function voidOrder($payment)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the void request
        if (isset($paymentInfo['id'])) {
            $request  = new Voids($paymentInfo['id']);
            $response = $this->checkoutApi->payments()->void($request);

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
     * @return mixed|void
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
            $request         = new Refund($paymentInfo['id']);
            $request->amount = $this->orderHandler->amountToGateway(
                $this->utilities->formatDecimals($amount * $order->getBaseToOrderRate()),
                $order
            );

            $response = $this->checkoutApi->payments()->refund($request);

            // Logging
            $this->logger->display($response);

            // Return the response
            return $response;
        }
    }

    /**
     * Gets payment details
     *
     * @param $paymentId
     *
     * @return mixed
     */
    public function getPaymentDetails($paymentId)
    {
        return $this->checkoutApi->payments()->details($paymentId);
    }

    /**
     * Creates a customer source
     *
     * @param $entity
     *
     * @return Customer
     */
    public function createCustomer($entity)
    {
        // Get the billing address
        $billingAddress = $entity->getBillingAddress();

        // Create the customer
        $customer        = new Customer();
        $customer->email = $billingAddress->getEmail();
        $customer->name  = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

        return $customer;
    }

    /**
     * Creates a billing address
     *
     * @param $entity
     *
     * @return Address
     */
    public function createBillingAddress($entity)
    {
        // Get the billing address
        $billingAddress = $entity->getBillingAddress();

        // Create the address
        $address                = new Address();
        $address->address_line1 = $billingAddress->getStreetLine(1);
        $address->address_line2 = $billingAddress->getStreetLine(2);
        $address->city          = $billingAddress->getCity();
        $address->zip           = $billingAddress->getPostcode();
        $address->country       = $billingAddress->getCountry();
        $address->state         = $billingAddress->getRegion();

        return $address;
    }

    /**
     * Creates a shipping address
     *
     * @param $entity
     *
     * @return Shipping
     */
    public function createShippingAddress($entity)
    {
        // Get the billing address
        $shippingAddress = $entity->getShippingAddress();

        // Create the address
        $address                = new Address();
        $address->address_line1 = $shippingAddress->getStreetLine(1);
        $address->address_line2 = $shippingAddress->getStreetLine(2);
        $address->city          = $shippingAddress->getCity();
        $address->zip           = $shippingAddress->getPostcode();
        $address->country       = $shippingAddress->getCountry();
        $address->state         = $shippingAddress->getRegion();

        return new Shipping($address);
    }

    /**
     * Get base metadata
     *
     * @return array
     * @throws NoSuchEntityException|FileSystemException
     */
    public function getBaseMetadata()
    {
        // Get the website URL
        $serverUrl = $this->storeManager->getStore()->getBaseUrl();

        // Get the SDK data
        $sdkData = 'PHP v' . phpversion() . ', SDK v' . CheckoutAPI::VERSION;

        // Get the integration data
        $integrationData = 'Checkout.com Magento 2 Module ';
        $integrationData .= $this->versionHandler->getModuleVersion('v');

        // Get the Magento version
        $platformData = 'Magento ' . $this->productMeta->getVersion();

        return [
            'udf5' => json_encode([
                'server_url'       => $serverUrl,
                'sdk_data'         => $sdkData,
                'integration_data' => $integrationData,
                'platform_data'    => $platformData,
            ]),
        ];
    }
}
