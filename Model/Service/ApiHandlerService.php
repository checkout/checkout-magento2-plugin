<?php

namespace CheckoutCom\Magento2\Model\Service;

use \Checkout\CheckoutApi;

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
     * Initialize the API client wrapper.
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->encryptor = $encryptor;
        $this->config = $config;

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
}
