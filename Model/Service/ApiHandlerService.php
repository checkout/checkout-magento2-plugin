<?php

namespace CheckoutCom\Magento2\Model\Service;

use \Checkout\CheckoutApi;
use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;

/**
 * Class for API handler service.
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
     * @var CheckoutApi
     */
    protected $utilities;


	/**
     * Initialize the API client wrapper.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    )
    {
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->utilities = $utilities;

        // Load the API client with credentials.
        $this->checkoutApi = $this->loadClient();
    }

	/**
     * Load the API client.
     */
    private function loadClient() {
        return new CheckoutApi(
            $this->config->getValue('secret_key'),
            $this->config->getValue('environment'),
            $this->config->getValue('public_key')
        );        
    }

    /**
     * Checks if a response is valid.
     */
    public function isValidResponse($response) {
        return is_object($response)
        && method_exists($response, 'isSuccessful')
        && $response->isSuccessful();
    }

    /**
     * Voids a transaction.
     */
    public function voidTransaction($payment) {
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

        return false;
    }

    /**
     * Refunds a transaction.
     */
    public function refundTransaction($payment, $amount) {
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

        return false;
    }

    /**
     * Gets payment detauls.
     */
    public function getPaymentDetails($paymentId) {
        return $this->payments()->details($paymentId);
    }
}
