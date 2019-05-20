<?php

namespace CheckoutCom\Magento2\Helper;

class Utilities {
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $config;

	/**
     * Utilities constructor.
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
        $this->config = $config;
	}
    
	/**
     * Checks if an external request is valid.
     */
    public function isValidAuth() {
        // Get the authorization header
        $authorization = $this->getRequest()->getHeader('Authorization');

        // Get the secret key from config
        $secretKey = $this->config->getValue('secret_key');
        
        return $authorization == $secretKey;
    }

	/**
     * Convert a date string to ISO8601 format.
     */
    public function formatDate($timestamp) {
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp); 
    }

    /**
     * Get the gateway payment information from an order
     */
    public function getPaymentData($order)
    {
        try {
            $paymentData = $order->getPayment()
            ->getMethodInstance()
            ->getInfoInstance()
            ->getData();

            return $paymentData['additional_information']['transaction_info'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add the gateway payment information to an order
     */
    public function setPaymentData($order, $data)
    {
        try {
            // Get the payment info instance
            $paymentInfo = $order->getPayment()->getMethodInstance()->getInfoInstance();

            // Add the transaction info for order save after
            $paymentInfo->setAdditionalInformation(
                'transaction_info',
                (array) $data
            );

            return $order;
        } catch (\Exception $e) {
            return false;
        }
    }
}