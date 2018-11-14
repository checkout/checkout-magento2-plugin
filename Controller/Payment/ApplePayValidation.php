<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Context;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Framework\Controller\Result\JsonFactory;

class ApplePayValidation extends AbstractAction {

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * PlaceOrder constructor.
     * @param Context $context
     * @param GatewayConfig $gatewayConfig
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GatewayConfig $gatewayConfig,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $gatewayConfig);

        $this->resultJsonFactory = $resultJsonFactory;
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
            $params = [];
            $params['merchantId']                = $this->gatewayConfig->getApplePayMerchantId();
            $params['domainName']                = $_SERVER["HTTP_HOST"];
            $params['displayName']               = 'My test shop';
            $params['processingCertificate']     = $this->gatewayConfig->getApplePayProcessingCertificate();
            $params['processingCertificatePass'] = $this->gatewayConfig->getApplePayProcessingCertificatePassword();
            $params['merchantCertificate']       = $this->gatewayConfig->getApplePayMerchantIdCertificate();
            $params['url']                       = $url;

            // create a new cURL resource
            $ch = curl_init();
            $data = '{"merchantIdentifier":"'. $params['merchantId'] .'", "domainName":"'. $params['domainName'] .'", "displayName":"'. $params['displayName'] .'"}';
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
}
