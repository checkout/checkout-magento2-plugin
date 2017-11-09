<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

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
use CheckoutCom\Magento2\Helper\Watchdog;

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
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * PaymentTokenService constructor.
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Watchdog $watchdog
    */
    public function __construct(
        GatewayConfig $gatewayConfig,
        TransferFactory $transferFactory,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Watchdog $watchdog
    ) {
        $this->gatewayConfig    = $gatewayConfig;
        $this->transferFactory  = $transferFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager  = $storeManager;
        $this->watchdog = $watchdog;
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
        // Get the quote object
        $quote = $this->checkoutSession->getQuote();

        // Get the reserved track id
        $trackId = $quote->reserveOrderId()->save()->getReservedOrderId();

        // Get the quote currency
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        // Get the quote amount
        $amount =  ChargeAmountAdapter::getPaymentFinalCurrencyValue($quote->getGrandTotal());

        // Prepare the amount 
        $value = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

        // Prepare the transfer data
        $transfer = $this->transferFactory->create([
            'value'   => $value,
            'currency'   => $currencyCode,
            'trackId' => $trackId
        ]);

        // Get the token
        try {
            $response = $this->getHttpClient('tokens/payment', $transfer)->request();
            
            $result   = (array) json_decode($response->getBody(), true);

            // Debug info
            $this->watchdog->bark($result);

            return isset($result['id']) ? $result['id'] : null;

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
