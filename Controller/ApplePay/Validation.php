<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\ApplePay;

class Validation extends \Magento\Framework\App\Action\Action {

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * Validation constructor.
     * @param Context $context
     * @param Config $config
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute() {
        // Get the validation URL from the request
        $url = $this->getRequest()->getParam('u');

        // Process the call after check
        if ("https" == parse_url($url, PHP_URL_SCHEME) && substr( parse_url($url, PHP_URL_HOST), -10 )  == ".apple.com" ) {
            // Prepare the configuration parameters
            $params = $this->getParams();

            // create a new cURL resource
            $ch = curl_init();

            // Prepare the data
            $data = '{"merchantIdentifier":"'
            . $params['merchantId'] 
            .'", "domainName":"'
            . $params['domainName'] 
            .'", "displayName":"'
            . $params['displayName'] 
            .'"}';

            // Initialise the request
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSLCERT, $params['merchantCertificate']);
            curl_setopt($ch, CURLOPT_SSLKEY, $params['processingCertificate']);
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $params['processingCertificatePass']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if (curl_exec($ch) === false)
            {
                echo '{"curlError":"' . curl_error($ch) . '"}';
            }

            // close cURL resource, and free up system resources
            curl_close($ch);
        }
    }

    public function getParams() {
        return [
            'merchantId' => $this->config->getValue('payment/checkoutcom_apple_pay/merchant_id'),
            'domainName' => $_SERVER["HTTP_HOST"],
            'displayName' => $this->config->getStoreName(),
            'processingCertificate' => $this->config->getValue('payment/checkoutcom_apple_pay/processing_certificate'),
            'processingCertificatePass' => $this->config->getValue('payment/checkoutcom_apple_pay/processing_certificate_password'),
            'merchantCertificate' => $this->config->getValue('payment/checkoutcom_apple_pay/merchant_id_certificate')
        ];
    }
}
