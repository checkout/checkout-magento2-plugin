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
use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;
use \Checkout\Models\Customer;

/**
 * Class ApiHandlerService.
 */
class ApiHandlerService
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    public $checkoutApi;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ApiHandlerService constructor.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;

        // Load the API client with credentials.
        $this->checkoutApi = $this->loadClient();
    }

    /**
     * Load the API client.
     */
    private function loadClient()
    {
        try {
            return new CheckoutApi(
                $this->config->getValue('secret_key'),
                $this->config->getValue('environment'),
                $this->config->getValue('public_key')
            );
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
            return is_object($response)
            && method_exists($response, 'isSuccessful')
            && $response->isSuccessful();
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

            // Process the void request
            if (isset($paymentInfo['id'])) {
                $request = new Refund($paymentInfo['id']);
                $request->amount = $amount*100;
                $response = $this->checkoutApi
                    ->payments()
                    ->refund($request);

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

            // Create the customer source
            $customer = new Customer($billingAddress->getEmail());
            $customer->name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

            return $customer;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
