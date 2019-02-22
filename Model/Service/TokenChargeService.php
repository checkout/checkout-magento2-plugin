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

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class TokenChargeService {

    const REDIRECT_URL = 'redirectUrl';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(
        Config $gatewayConfig,
        TransferFactory $transferFactory,
        Logger $logger,
        ManagerInterface $messageManager,
        Watchdog $watchdog,
        CheckoutSession $checkoutSession,
        ConfigProvider $configProvider
    ) {
        $this->logger                   = $logger;
        $this->gatewayConfig            = $gatewayConfig;
        $this->transferFactory          = $transferFactory;
        $this->messageManager           = $messageManager;
        $this->watchdog                 = $watchdog;
        $this->checkoutSession          = $checkoutSession;
        $this->configProvider           = $configProvider;
    }

    public function sendApplePayChargeRequest($payload, $quote) {
        // Build the request URL
        $url = $this->gatewayConfig->getApplePayTokenRequestUrl();

        // Prepare the token request data
        $transfer = $this->transferFactory->create([
            'type' => 'applepay',
            'token_data' => $payload['paymentData']
        ]);
            
        // Send the token request
        $response  = $this->getHttpClient($url, $transfer, $needsPublicKey = true)->request();
        $tokenData = json_decode($response->getBody(), true);

        // Send the charge request
        $result = $this->sendChargeRequest($tokenData['token'], $quote, $disable3ds = true, $isQuote = true);

        return $result;
    }

    public function sendGooglePayChargeRequest($payload, $quote) {
        // Build the request URL
        $url = $this->gatewayConfig->getGooglePayTokenRequestUrl();

        // Prepare the token request data
        $transfer = $this->transferFactory->create([
            'type' => 'googlepay',
            'token_data' => $payload
        ]);
            
        // Send the token request
        $response  = $this->getHttpClient($url, $transfer, $needsPublicKey = true)->request();
        $tokenData = json_decode($response->getBody(), true);

        // Send the charge request
        $result = $this->sendChargeRequest($tokenData['token'], $quote, $disable3ds = true, $isQuote = true);

        return $result;
    }

    public function sendChargeRequest($cardToken, $entity, $disable3ds = false, $isQuote = false) {
        // Prepare some variables
        $chargeMode = ($this->gatewayConfig->isVerify3DSecure() || !$disable3ds) ? 2 : 1;
        $attemptN3D = ((filter_var($this->gatewayConfig->isAttemptN3D(), FILTER_VALIDATE_BOOLEAN)) && !$disable3ds); 
        $trackId = ($isQuote) ? $entity->reserveOrderId()->save()->getReservedOrderId() : $entity->getIncrementId();

        // Set the request parameters
        $url = $this->gatewayConfig->getApiUrl() . 'charges/token';
        $transfer = $this->transferFactory->create([
            'autoCapTime'   => $this->gatewayConfig->getAutoCaptureTimeInHours(),
            'autoCapture'   => $this->gatewayConfig->isAutoCapture() ? 'Y' : 'N',
            'email'         => $entity->getBillingAddress()->getEmail(),
            'customerIp'    => $entity->getRemoteIp(),
            'chargeMode'    => $chargeMode,
            'attemptN3D'    => $attemptN3D,
            'customerName'  => $entity->getCustomerName(),
            'currency'      => ChargeAmountAdapter::getPaymentFinalCurrencyCode($entity->getCurrencyCode()),
            'value'         => $entity->getGrandTotal()*100,
            'trackId'       => $trackId,
            'cardToken'     => $cardToken,
            'successUrl'    => $this->configProvider->getSuccessUrl(),
            'failUrl'       => $this->configProvider->getFailUrl()
        ]);

        // Handle the request
        return $this->_handleRequest($url, $transfer);
    }

    protected function _handleRequest($url, $transfer) {
        // Prepare log data
        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => $url,
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => $transfer->getMethod(),
        ];

        try {
            // Send the request
            $response           = $this->getHttpClient($url, $transfer)->request();
            $result             = json_decode($response->getBody(), true);

            // Handle the response
            $result = $this->_handleResponse($result);

            // Log the response
            $log['response']    = $result;

            return $result;
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
        finally {
            $this->logger->debug($log);
        } 
    }

    protected function _handleResponse($response) {
        // Debug info
        $this->watchdog->bark($response);

        // Handle response code
        if (isset($response['responseCode']) && ((int) $response['responseCode'] == 10000 || (int) $response['responseCode'] == 10100)) {
            // Prepare 3D Secure redirection with session variable for pre auth order
            if (array_key_exists(self::REDIRECT_URL, $response)) {
                
                // Get the 3DS redirection URL
                $redirectUrl = $response[self::REDIRECT_URL];
                
                // Set 3DS redirection in session for the PlaceOrder controller
                $this->checkoutSession->set3DSRedirect($redirectUrl);

                // Put the response in session for the PlaceOrder controller
                $this->checkoutSession->setGatewayResponseId($response['id']);
            }

            return true;
        }
        else {
            if (isset($response['responseMessage'])) {
                $this->messageManager->addErrorMessage($response['responseMessage']);
            }             
        }

        return false;
    }

    /**
     * Returns prepared HTTP client.
     *
     * @param string $endpoint
     * @param TransferInterface $transfer
     * @return ZendClient
     * @throws \Exception
     */
    private function getHttpClient($endpoint, TransferInterface $transfer, $needsPublicKey = false) {
        // Prepare the headers
        $headers = $transfer->getHeaders();
        if ($needsPublicKey) {
            $headers['Authorization'] = $this->gatewayConfig->getPublicKey();
        }

        // Build the client
        $client = new ZendClient($endpoint);
        $client->setMethod('POST');
        $client->setRawData( json_encode( $transfer->getBody()) ) ;
        $client->setHeaders($headers);
        
        return $client;
    }
}
