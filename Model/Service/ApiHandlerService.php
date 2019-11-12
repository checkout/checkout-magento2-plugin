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

/**
 * Class ApiHandlerService.
 */
class ApiHandlerService
{
    /**
     * @var EncryptorInterface
     */
    public $encryptor;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    public $productMeta;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var CheckoutApi
     */
    public $checkoutApi;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * ApiHandlerService constructor.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMeta,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler
    ) {
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->productMeta = $productMeta;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->orderHandler = $orderHandler;
    }

    /**
     * Load the API client.
     */
    public function init($storeCode = null)
    {
        $this->checkoutApi = new CheckoutApi(
            $this->config->getValue('secret_key', null, $storeCode),
            $this->config->getValue('environment', null, $storeCode),
            $this->config->getValue('public_key', null, $storeCode)
        );
        
        return $this;
    }

    /**
     * Checks if a response is valid.
     */
    public function isValidResponse($response)
    {
        return $response != null
        && is_object($response)
        && method_exists($response, 'isSuccessful')
        && $response->isSuccessful();
    }

    /**
     * Voids a transaction.
     */
    public function voidOrder($payment)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);

        // Process the void request
        if (isset($paymentInfo['id'])) {
            $request = new Voids($paymentInfo['id']);
            $response = $this->checkoutApi
                ->payments()
                ->void($request);

            // Logging
            $this->logger->display($response);

            return $response;
        }
    }

    /**
     * Refunds a transaction.
     */
    public function refundOrder($payment, $amount)
    {
        // Get the order
        $order = $payment->getOrder();

        // Get the payment info
        $paymentInfo = $this->utilities->getPaymentData($order);
        
        // Process the refund request
        if (isset($paymentInfo['id'])) {
            $request = new Refund($paymentInfo['id']);
            $request->amount = $this->orderHandler->amountToGateway($amount, $order);
            $response = $this->checkoutApi
                ->payments()
                ->refund($request);

            // Logging
            $this->logger->display($response);
            
            // Apply the order status
            if ($order->getGrandTotal() == $order->getTotalRefunded()) {
                $order->setState('order_status_refunded');
                $order->setStatus($this->config->getValue('order_status_refunded'));
            }
            else {
                $order->setState($this->config->getValue('order_status_refunded_partial'));
                $order->setStatus($this->config->getValue('order_status_refunded_partial'));
            }

            // Return the response
            return $response;
        }
    }

    /**
     * Gets payment details.
     */
    public function getPaymentDetails($paymentId)
    {
        return $this->checkoutApi->payments()->details($paymentId);
    }

    /**
     * Creates a customer source.
     */
    public function createCustomer($entity)
    {
        // Get the billing address
        $billingAddress = $entity->getBillingAddress();

        // Create the customer
        $customer = new Customer();
        $customer->email = $billingAddress->getEmail();
        $customer->name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

        return $customer;
    }

    /**
     * Creates a billing address.
     */
    public function createBillingAddress($entity)
    {
        // Get the billing address
        $billingAddress = $entity->getBillingAddress();

        // Create the address
        $address = new Address();
        $address->address_line1 = $billingAddress->getStreetLine(1);
        $address->address_line2 = $billingAddress->getStreetLine(2);
        $address->city = $billingAddress->getCity();
        $address->zip = $billingAddress->getPostcode();
        $address->country = $billingAddress->getCountry();

        return $address;
    }

    /**
     * Creates a shipping address.
     */
    public function createShippingAddress($entity)
    {
        // Get the billing address
        $shippingAddress = $entity->getBillingAddress();

        // Create the address
        $address = new Address();
        $address->address_line1 = $shippingAddress->getStreetLine(1);
        $address->address_line2 = $shippingAddress->getStreetLine(2);
        $address->city = $shippingAddress->getCity();
        $address->zip = $shippingAddress->getPostcode();
        $address->country = $shippingAddress->getCountry();
        
        return new Shipping($address);
    }

    /**
     * Get base metadata.
     */
    public function getBaseMetadata()
    {
        // Get the website URL
        $serverUrl = $this->storeManager->getStore()->getBaseUrl();

        // Get the SDK data
        $sdkData = 'PHP v' . phpversion() . ', SDK v' . CheckoutAPI::VERSION;

        // Get the integration data
        $integrationData  = 'Checkout.com Magento 2 Module v';
        $integrationData .= $this->config->getModuleVersion();

        // Get the Magento version
        $platformData = 'Magento ' . $this->productMeta->getVersion();

        return [
            'server_url' => $serverUrl,
            'sdk_data' => $sdkData,
            'integration_data' => $integrationData,
            'platform_data' => $platformData
        ];     
    }
}
