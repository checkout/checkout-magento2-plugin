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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\ApplePay;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class Validation
 */
class Validation extends Action
{
    /**
     * $rawFactory field
     *
     * @var RawFactory $rawFactory
     */
    private $rawFactory;
    /**
     * $curl field
     *
     * @var Curl $curl
     */
    private $curl;
    /**
     * $config field
     *
     * @var Config
     */
    private $config;

    /**
     * Validation constructor
     *
     * @param Context    $context
     * @param RawFactory $rawFactory
     * @param Curl       $curl
     * @param Config     $config
     */
    public function __construct(
        Context $context,
        RawFactory $rawFactory,
        Curl $curl,
        Config $config
    ) {
        parent::__construct($context);

        $this->rawFactory = $rawFactory;
        $this->curl       = $curl;
        $this->config     = $config;
    }

    /**
     * Handles the controller method.
     *
     * @return Raw
     * @throws NoSuchEntityException
     */
    public function execute(): Raw
    {
        // Get request parameters
        $methodId = $this->getRequest()->getParam('method_id');
        $url      = $this->getRequest()->getParam('u');

        if (substr($url, 0, 5) === 'https' && substr($url, 0, 8) !== 'https://') {
            $url = 'https://' . substr($url, 7);
        }

        // Prepare the configuration parameters
        $params = $this->getParams($methodId);

        // Prepare the data
        $data = $this->buildDataString($params);

        // Initialize the request
        $this->curl->setOption(CURLOPT_SSLCERT, $params['merchantCertificate']);
        $this->curl->setOption(CURLOPT_SSLKEY, $params['processingCertificate']);
        $this->curl->setOption(CURLOPT_SSLKEYPASSWD, $params['processingCertificatePass']);
        $this->curl->setOption(CURLOPT_POSTFIELDS, $data);

        // Send the request
        $this->curl->post($url, []);

        // Return the response
        return $this->rawFactory->create()->setContents(
            $this->curl->getBody()
        );
    }

    /**
     * Build the Apple Pay data string
     *
     * @param string[] $params
     *
     * @return string
     */
    public function buildDataString(array $params): string
    {
        return '{"merchantIdentifier":"' . $params['merchantId'] . '", "domainName":"' . $params['domainName'] . '", "displayName":"' . $params['displayName'] . '"}';
    }

    /**
     * Prepare the Apple Pay request parameters
     *
     * @param string $methodId
     *
     * @return string[]
     * @throws NoSuchEntityException
     */
    protected function getParams(string $methodId): array
    {
        return [
            'merchantId'                => $this->config->getValue(
                'merchant_id',
                $methodId
            ),
            'domainName'                => $this->getRequest()->getServer('HTTP_HOST'),
            'displayName'               => $this->config->getStoreName(),
            'processingCertificate'     => $this->config->getValue(
                'processing_certificate',
                $methodId
            ),
            'processingCertificatePass' => $this->config->getValue(
                'processing_certificate_password',
                $methodId
            ),
            'merchantCertificate'       => $this->config->getValue(
                'merchant_id_certificate',
                $methodId
            ),
        ];
    }
}
