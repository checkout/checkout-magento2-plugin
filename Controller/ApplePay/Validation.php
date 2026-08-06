<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\ApplePay;

use CheckoutCom\Magento2\Gateway\Config\Config;
use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Webapi\Exception as WebException;
use Psr\Log\LoggerInterface;

/**
 * Class Validation
 */
class Validation extends Action
{
    /**
     * Apple's merchant validation endpoints: production, -nc-pod, -pr-pod and -dr pods,
     * -cert sandbox, and the China (cn-) variants.
     */
    private const ALLOWED_HOST_PATTERN = '/^(?:cn-)?apple-pay-gateway(?:-[a-z0-9-]+)?\.apple\.com$/i';

    /**
     * BAD GATEWAY Error code.
     */
    private const HTTP_BAD_GATEWAY = 502;

    public function __construct(
        Context $context,
        private RawFactory $rawFactory,
        private Curl $curl,
        private Config $config,
        private LoggerInterface $logger,
        private JsonSerializer $jsonSerializer
    ) {
        parent::__construct($context);
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
        $url = $this->getRequest()->getParam('u');

        if (str_starts_with($url, 'https') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . substr($url, 7);
        }

        if (!$this->isAllowedApplePayUrl($url)) {
            $this->logger->warning(
                'Apple Pay validation request rejected: URL is not an allow-listed Apple endpoint.',
                ['url' => $url]
            );

            return $this->jsonResult(['error' => 'Invalid Apple Pay validation URL.'], WebException::HTTP_BAD_REQUEST);
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
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, false);

        // Send the request
        $this->curl->post($url, []);

        $body = $this->curl->getBody();
        try {
            $this->jsonSerializer->unserialize($body);
        } catch (InvalidArgumentException) {
            $this->logger->warning('Apple Pay validation rejected: upstream response was not valid JSON.');

            return $this->jsonResult(['error' => 'Invalid response from Apple Pay endpoint.'], self::HTTP_BAD_GATEWAY);
        }

        // Return the response. Content-Type/nosniff are enforced so the body can never be
        // rendered as markup by the browser, regardless of what the upstream host sends.
        return $this->rawFactory->create()
            ->setHeader('Content-Type', 'application/json', true)
            ->setHeader('X-Content-Type-Options', 'nosniff', true)
            ->setContents($body);
    }

    /**
     * Whether the given URL is an allow-listed Apple Pay merchant validation endpoint.
     *
     * @param mixed $url
     * @return bool
     */
    private function isAllowedApplePayUrl(mixed $url): bool
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if ($parts['scheme'] !== 'https') {
            return false;
        }

        // No userinfo in the authority (e.g. https://apple-pay-gateway.apple.com@evil.test/).
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        // No port other than the implicit/explicit 443.
        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        return (bool) preg_match(self::ALLOWED_HOST_PATTERN, $parts['host']);
    }

    /**
     * Build a JSON result that cannot be sniffed/rendered as markup by the browser.
     *
     * @param array $payload
     * @param int $httpCode
     * @return Raw
     */
    private function jsonResult(array $payload, int $httpCode): Raw
    {
        return $this->rawFactory->create()
            ->setHttpResponseCode($httpCode)
            ->setHeader('Content-Type', 'application/json', true)
            ->setHeader('X-Content-Type-Options', 'nosniff', true)
            ->setContents($this->jsonSerializer->serialize($payload));
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
            'merchantId' => $this->config->getValue(
                'merchant_id',
                $methodId
            ),
            'domainName' => $this->getRequest()->getServer('HTTP_HOST'),
            'displayName' => $this->config->getStoreName(),
            'processingCertificate' => $this->config->getValue(
                'processing_certificate',
                $methodId
            ),
            'processingCertificatePass' => $this->config->getValue(
                'processing_certificate_password',
                $methodId
            ),
            'merchantCertificate' => $this->config->getValue(
                'merchant_id_certificate',
                $methodId
            ),
        ];
    }
}
