<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Framework\App\ObjectManager;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Gateway\Exception\ApiClientException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use Zend_Http_Client_Exception;

class VerifyPaymentService {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * VerifyPaymentService constructor.
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
     */
    public function __construct(GatewayConfig $gatewayConfig, TransferFactory $transferFactory) {
        $this->gatewayConfig    = $gatewayConfig;
        $this->transferFactory  = $transferFactory;
    }

    /**
     * Runs the service.
     *
     * @param $paymentToken
     * @return array
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    public function verifyPayment($paymentToken) {
        $transfer = $this->transferFactory->create([]);

        try {
            $response = $this->getHttpClient('charges/' . $paymentToken, $transfer)->request();
            $result   = (array) json_decode($response->getBody(), true);

            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }

            $this->putGatewayResponseToHolder($result);

            return $result;
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
    }

    /**
     * Returns prepared HTTP client.
     *
     * @param string $endpoint
     * @param TransferInterface $transfer
     * @return ZendClient
     * @throws \Exception
     */
    private function getHttpClient($endpoint, TransferInterface $transfer) {
        $client = new ZendClient($this->gatewayConfig->getApiUrl() . $endpoint);
        $client->setMethod('GET');
        $client->setHeaders($transfer->getHeaders());
        $client->setUrlEncodeBody($transfer->shouldEncode());

        return $client;
    }

    /**
     * Sets the gateway response to the holder.
     *
     * @param array $response
     * @throws \RuntimeException
     */
    private function putGatewayResponseToHolder(array $response) {
        /* @var $gatewayResponseHolder GatewayResponseHolder */
        $gatewayResponseHolder = ObjectManager::getInstance()->get(GatewayResponseHolder::class);
        $gatewayResponseHolder->setGatewayResponse($response);
    }

}
