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

/**
 * Class Validation
 */
class Validation extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Curl
     */
    protected $curl;

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
        \Magento\Framework\HTTP\Client\Curl $curl,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->curl = $curl;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            // Get request parameters
            $this->methodId = $this->getRequest()->getParam('method_id');
            $this->url = $this->getRequest()->getParam('u');

            // Process the call after check
            if ($this->getRequest()->isAjax()) {
                // Prepare the configuration parameters
                $params = $this->getParams();

                // Prepare the data
                $data = '{"merchantIdentifier":"'
                . $params['merchantId']
                .'", "domainName":"'
                . $params['domainName']
                .'", "displayName":"'
                . $params['displayName']
                .'"}';

                // Initialize the request
                $this->curl->curlOptions([
                    CURLOPT_SSLCERT => $params['merchantCertificate'],
                    CURLOPT_SSLKEY => $params['processingCertificate'],
                    CURLOPT_SSLKEYPASSWD => $params['processingCertificatePass'],
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_RETURNTRANSFER => true
                ]);

                // Send the request
                $this->curl->post($this->url, []);

                // Return the response
                return $this->curl->getBody();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
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
                'merchantId' => $this->config->getValue(
                    'merchant_id',
                    $this->methodId
                ),
                'domainName' => $this->getRequest()->getServer('HTTP_HOST'),
                'displayName' => $this->config->getStoreName(),
                'processingCertificate' => $this->config->getValue(
                    'processing_certificate',
                    $this->methodId
                ),
                'processingCertificatePass' => $this->config->getValue(
                    'processing_certificate_password',
                    $this->methodId
                ),
                'merchantCertificate' => $this->config->getValue(
                    'merchant_id_certificate',
                    $this->methodId
                )
            ];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return [];
        }
    }
}
