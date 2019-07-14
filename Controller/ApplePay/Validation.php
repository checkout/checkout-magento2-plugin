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
    public $jsonFactory;

    /**
     * @var Curl
     */
    public $curl;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Config
     */
    public $config;

    /**
     * Validation constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->config = $config;
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

            // Prepare the configuration parameters
            $params = $this->getParams();

            // Prepare the data
            $data = $this->buildDataString($params);

            // Initialize the request
            $this->curl->setOption(CURLOPT_SSLCERT, $params['merchantCertificate']);
            $this->curl->setOption(CURLOPT_SSLKEY, $params['processingCertificate']);
            $this->curl->setOption(CURLOPT_SSLKEYPASSWD, $params['processingCertificatePass']);
            $this->curl->setOption(CURLOPT_POSTFIELDS, $data);

            // Send the request
            $this->curl->post($this->url, []);

            // Return the response
            print $this->curl->getBody();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Build the Apple Pay data string
     *
     * @return array
     */
    public function buildDataString($params)
    {
        return '{"merchantIdentifier":"'
            . $params['merchantId']
            .'", "domainName":"'
            . $params['domainName']
            .'", "displayName":"'
            . $params['displayName']
            .'"}';
    }

    /**
     * Prepare the Apple Pay request parameters
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
            return $this->jsonFactory->create()->setData([]);
        }
    }
}
