<?php

namespace CheckoutCom\Magento2\Model\Service;

use \Checkout\CheckoutApi;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\ThreeDs;

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
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $checkoutApi;

    /**
     * @var Payment
     */
    protected $request;

    /**
     * @var Payment
     */
    protected $response;

	/**
     * Initialize the API client wrapper.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->config = $config;

        // Load the API client with credentials.
        $this->checkoutApi = $this->loadClient();
    }

	/**
     * Load the API client.
     */
    private function loadClient() {
        return new CheckoutApi(
            $this->encryptor->decrypt(
                $this->config->getValue('secret_key')
            ),
            $this->config->getValue('environment'),
            $this->encryptor->decrypt(
                $this->config->getValue('public_key')
            )
        );        
    }

	/**
     * Send a charge request.
     */
    public function sendChargeRequest($methodId, $cardToken, $amount, $currency, $reference = '') {
        // Set the token source
        $tokenSource = new TokenSource($cardToken);

        // Set the payment
        $this->request = new Payment(
            $tokenSource, 
            $currency
        );

        // Set the request parameters
        $this->request->capture = $this->config->isAutoCapture();
        $this->request->amount = $amount*100;
        $this->request->reference = $reference;
        $this->request->description = _(
            'Payment request from %1', $this->config->getStoreName()
        );
        $this->request->threeDs = new ThreeDs(
            $this->config->getValue(
                'three_ds', $methodId
            )
        );

        // Auto capture time setting
        // Todo - Check capture time missing in SDK?
        if ($this->config->isAutoCapture()) {
            $this->request->capture_on = $this->config->getValue(
                'capture_on', $methodId
            );
        }

        // Send the charge request
        $this->response = $this->checkoutApi
            ->payments()
            ->request($this->request);

        return $this;
    }

	/**
     * Process a charge response.
     */
    public function getResponse() {
        return $this->response;
    } 
}
