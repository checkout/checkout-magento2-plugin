<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\ApplePay;

class Validation extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Validation constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;

        // Get request parameters
        $this->methodId = $this->getRequest()->getParam('method_id');
        $this->url = $this->getRequest()->getParam('u');
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            // Process the call after check
            if ($this->isValidRequest()) {
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

                // Initialize the request
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_SSLCERT, $params['merchantCertificate']);
                curl_setopt($ch, CURLOPT_SSLKEY, $params['processingCertificate']);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $params['processingCertificatePass']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                if (curl_exec($ch) === false) {
                    echo '{"curlError":"' . curl_error($ch) . '"}';
                }

                // close cURL resource, and free up system resources
                curl_close($ch);
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Checks if the request is valid.
     *
     * @return boolean
     */
    protected function isValidRequest()
    {
        return "https" == parse_url($this->url, PHP_URL_SCHEME)
        && substr(parse_url($this->url, PHP_URL_HOST), -10)  == ".apple.com";
    }

    /**
     * Prepare the Apple Pay request parameters.
     *
     * @return array
     */
    public function getParams()
    {
        try {
            return [
                'merchantId' => $this->config->getValue('merchant_id', $this->methodId),
                'domainName' => $_SERVER["HTTP_HOST"],
                'displayName' => $this->config->getStoreName(),
                'processingCertificate' => $this->config->getValue('processing_certificate', $this->methodId),
                'processingCertificatePass' => $this->config->getValue('processing_certificate_password', $this->methodId),
                'merchantCertificate' => $this->config->getValue('merchant_id_certificate', $this->methodId)
            ];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return [];
        }
    }
}
