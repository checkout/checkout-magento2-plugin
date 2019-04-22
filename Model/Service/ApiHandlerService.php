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
     * Convert a date string to ISO8601 format.
     */
    protected function formatDate($dateString) {
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
    protected function setCaptureDate($methodId) {
        try {
            // Get the  capture date from config
            $captureDate = $this->config->getValue('capture_on', $methodId);

            // Check the setting
            if ($this->config->isAutoCapture($methodId) && !empty($captureDate)) {
                // Todo - Check capture time missing in SDK?
                $this->request->capture_on = $this->formatDate($captureDate);
            }
        }
        catch(\Exception $e) {
            return null;
        }
    }

	/**
     * Send a charge request.
     */
    public function sendChargeRequest($methodId, $cardToken, $amount, $currency, $reference = '') {
        try {
            // Set the token source
            $tokenSource = new TokenSource($cardToken);

            // Set the payment
            $this->request = new Payment(
                $tokenSource, 
                $currency
            );

            // Set the request parameters
            $this->request->capture = $this->config->isAutoCapture($methodId);
            $this->request->amount = $amount*100;
            $this->request->reference = $reference;
            $this->request->threeDs = new ThreeDs(
                $this->config->getValue(
                    'three_ds', $methodId
                )
            );
            /*
            $this->request->description = _(
                'Payment request from %1', $this->config->getStoreName()
            );
            */

            // Auto capture time setting
            //$this->setCaptureDate($methodId);

            // Send the charge request
            $this->response = $this->checkoutApi
                ->payments()
                ->request($this->request);


            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/response.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($this->response, 1));

            return $this;

        }   
        catch(\Exception $e) {
            // Todo - remove logging code
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($e->getMessage(), 1));
        }
    }

	/**
     * Process a charge response.
     */
    public function getResponse() {
        return $this->response;
    } 
}
