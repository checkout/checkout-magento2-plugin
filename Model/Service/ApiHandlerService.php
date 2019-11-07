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
     * @var QuoteHandlerService
     */
    public $quoteHandler;

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
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->productMeta = $productMeta;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->quoteHandler = $quoteHandler;
    }

    /**
     * Load the API client.
     */
    public function init($storeCode = null)
    {
        try {
            $this->checkoutApi = new CheckoutApi(
                $this->config->getValue('secret_key', null, $storeCode),
                $this->config->getValue('environment', null, $storeCode),
                $this->config->getValue('public_key', null, $storeCode)
            );
            
            return $this;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Checks if a response is valid.
     */
    public function isValidResponse($response)
    {
        try {
            return $response != null
            && is_object($response)
            && method_exists($response, 'isSuccessful')
            && $response->isSuccessful();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Captures a transaction.
     */
    public function captureOrder($payment, $amount)
    {
        try {
            // Get the order
            $order = $payment->getOrder();

            // Get the payment info
            $paymentInfo = $this->utilities->getPaymentData($order);

            // Process the capture request
            if (isset($paymentInfo['id'])) {
                // Initialise the capture object
                $request = new Capture($paymentInfo['id']);

                // Handle partial capture
                $paymentAmount = $this->utilities->formatDecimals($amount);
                $orderAmount = $this->utilities->formatDecimals($order->getGrandTotal());
                if ($paymentAmount < $orderAmount && $payment->canCapturePartial()) {
                    $payment->amount = $paymentAmount;
                }

                // Process the request
                $response = $this->checkoutApi
                    ->payments()
                    ->capture($request);

                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Voids a transaction.
     */
    public function voidOrder($payment)
    {
        try {
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

                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Refunds a transaction.
     */
    public function refundOrder($payment, $amount)
    {
        try {
            // Get the order
            $order = $payment->getOrder();

            // Get the payment info
            $paymentInfo = $this->utilities->getPaymentData($order);

            // Process the refund request
            if (isset($paymentInfo['id'])) {
                $request = new Refund($paymentInfo['id']);
                $request->amount = $this->quoteHandler->prepareAmount($amount, $quote);
                $response = $this->checkoutApi
                    ->payments()
                    ->refund($request);

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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Gets payment details.
     */
    public function getPaymentDetails($paymentId)
    {
        try {
            return $this->checkoutApi->payments()->details($paymentId);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Creates a customer source.
     */
    public function createCustomer($entity)
    {
        try {
            // Get the billing address
            $billingAddress = $entity->getBillingAddress();

            // Create the customer
            $customer = new Customer();
            $customer->email = $billingAddress->getEmail();
            $customer->name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

            return $customer;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Creates a billing address.
     */
    public function createBillingAddress($entity)
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Creates a shipping address.
     */
    public function createShippingAddress($entity)
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
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
