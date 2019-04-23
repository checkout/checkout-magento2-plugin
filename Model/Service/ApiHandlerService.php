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
     * Convert a date string to ISO8601 format.
     */
    public function formatDate($dateString) {
        try {
            $datetime = new \DateTime($dateString);
            return $datetime->format(\DateTime::ATOM);
        } 
        catch(\Exception $e) {
            return null;
        }
    }

	/**
     * Checks and sets a capture time for the request.
     */
    public function setCaptureDate($methodId, $request) {
        try {
            // Get the  capture date from config
            $captureDate = $this->config->getValue('capture_on', $methodId);

            // Check the setting
            if ($this->config->isAutoCapture($methodId) && !empty($captureDate)) {
                // Todo - Check capture time missing in SDK?
                $request->capture_on = $this->formatDate($captureDate);
            }

            return $request;
        }
        catch(\Exception $e) {
            return null;
        }
    }
}
