<?php

namespace CheckoutCom\Magento2\Model\Service;

use Zend_Http_Client_Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Gateway\Exception\ApiClientException;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;

class PaymentTokenService {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PaymentTokenService constructor.
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
    */
    public function __construct(GatewayConfig $gatewayConfig, TransferFactory $transferFactory, Session $checkoutSession, StoreManagerInterface $storeManager) {
        $this->gatewayConfig    = $gatewayConfig;
        $this->transferFactory  = $transferFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager  = $storeManager;
    }

    /**
     * Runs the service.
     *
     * @return array
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    public function getToken() {
        // Get the quote currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        // Get the quote amount
        $amount       =  ChargeAmountAdapter::getPaymentFinalCurrencyValue($this->checkoutSession->getQuote()->getGrandTotal());

        // Prepare the amount 
        $value        = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

        // Prepare the transfer data
        $transfer = $this->transferFactory->create([
            'value'   => $value,
            'currency'   => $currencyCode
        ]);

        // Get the token
        try {
            $response = $this->getHttpClient('tokens/payment', $transfer)->request();
            
            $result   = (array) json_decode($response->getBody(), true);

            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }

            return $result['id'];

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
        $client->setMethod('POST');
        $client->setRawData( json_encode( $transfer->getBody()) ) ;
        $client->setHeaders($transfer->getHeaders());
        $client->setConfig($transfer->getClientConfig());
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
