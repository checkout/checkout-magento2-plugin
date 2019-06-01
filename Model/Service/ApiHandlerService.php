<?php

namespace CheckoutCom\Magento2\Model\Service;

use \Checkout\CheckoutApi;
use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;
use \Checkout\Models\Payments\CustomerSource;

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
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

	/**
     * Initialize the API client wrapper.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    )
    {
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->quoteHandler = $quoteHandler;

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
        return $this->checkoutApi->payments()->details($paymentId);
    }

    /**
     * Creates a customer source.
     */
    public function createCustomerSource() {
        // Find the quote
        $quote = $this->quoteHandler->getQuote();

        // Get the customer email
        $customerEmail = $this->quoteHandler->findEmail($quote);

        // Get the billing address
        $billingAddress = $this->quoteHandler->getBillingAddress();

        // Create the customer source
        $customerSource = new CustomerSource($customerEmail);
        $customerSource->name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

        return $customerSource;
    }
}
