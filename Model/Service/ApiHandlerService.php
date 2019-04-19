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
    protected $checkoutApi;

	/**
     * Initialize SDK wrapper.
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    )
    {
        $this->config = $config;
        $this->encryptor = $encryptor;

        // Load the API client with credentials.
        $this->loadClient();
    }


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
     * Set the request parameters .
     */
    public function setParams() {

var_dump(
    $this->encryptor->decrypt(
        $this->config->getValue('secret_key')
    )
);
exit();

        echo "<pre>";
        var_dump(get_class_methods($this->checkoutApi));
        echo "</pre>";

        return $this;
    }

    public function sendChargeRequest() {

        return $this;
    }
}
