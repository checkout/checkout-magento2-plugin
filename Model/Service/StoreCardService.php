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

use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use CheckoutCom\Magento2\Model\Factory\VaultTokenFactory;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Gateway\Exception\ApiClientException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use Zend_Http_Client_Exception;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use CheckoutCom\Magento2\Helper\Watchdog;

class StoreCardService {

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var VaultTokenFactory
     */
    protected $vaultTokenFactory;

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var string
     */
    protected $customerEmail;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $cardToken;

    /**
     * @var array
     */
    protected $cardData = [];

    /**
     * @var array
     */
    protected $authorizedResponse = [];

    /**
     * @var ResponseFactory 
     */
    protected $responseFactory;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    protected $scopeConfig;
    
    protected $customerSession;
    
    /**
     * StoreCardService constructor.
     * @param Logger $logger
     * @param VaultTokenFactory $vaultTokenFactory
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param ResponseFactory $responseFactory
     * @param Watchdog $watchdog
     */
    public function __construct(
        Logger $logger,
        VaultTokenFactory $vaultTokenFactory,
        GatewayConfig $gatewayConfig,
        TransferFactory $transferFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ResponseFactory $responseFactory,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        Watchdog $watchdog
    ) {
        $this->logger                   = $logger;
        $this->vaultTokenFactory        = $vaultTokenFactory;
        $this->gatewayConfig            = $gatewayConfig;
        $this->transferFactory          = $transferFactory;
        $this->paymentTokenRepository   = $paymentTokenRepository;
        $this->paymentTokenManagement   = $paymentTokenManagement;
        $this->scopeConfig              = $scopeConfig;
        $this->responseFactory          = $responseFactory;
        $this->customerSession          = $customerSession;
        $this->watchdog = $watchdog;
    }

    /**
     * Sets the customer ID.
     *
     * @param int $customerId
     * @return StoreCardService
     */
    public function setCustomerId($id = null) {
        $this->customerId = (int) $id > 0 ? $id : $this->customerSession->getCustomer()->getId();

        return $this;
    }

    /**
     * Sets the customer email address.
     *
     * @param string $customerEmail
     * @return StoreCardService
     */
    public function setCustomerEmail() {
        $this->customerEmail = $this->customerSession->getCustomer()->getEmail();

        return $this;
    }

    /**
     * Sets the card token.
     *
     * @param string $cardToken
     * @return StoreCardService
     */
    public function setCardToken($cardToken) {
        $this->cardToken    = $cardToken;

        return $this;
    }


    /**
     * Sets the card data.
     *
     * @return StoreCardService
     */
    public function setCardData() {

        // Prepare the card data to save
        $cardData = $this->authorizedResponse['card'];
        unset($cardData['customerId']);
        unset($cardData['billingDetails']);
        unset($cardData['bin']);
        unset($cardData['fingerprint']);
        unset($cardData['cvvCheck']);
        unset($cardData['name']);
        unset($cardData['avsCheck']);

        // Assign the card data
        $this->cardData = $cardData;

        return $this;
    }

    /**
     * Tests the card through gateway.
     *
     * @return StoreCardService
     */
    public function test() {

        // Perform the authorization
        $this->authorizeTransaction();

        // Validate the authorization
        $this->validateAuthorization();

        // Perform the void
        $this->voidTransaction();

        return $this;
    }

    /**
     * Saves the credit card in the repository.
     *
     * @throws LocalizedException
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    public function save() {

        // Create the payment token from response
        $paymentToken = $this->vaultTokenFactory->create($this->cardData, $this->customerId);
        $foundPaymentToken  = $this->foundExistedPaymentToken($paymentToken);

        // Check if card exists
        if ($foundPaymentToken) {
            if($foundPaymentToken->getIsActive()) {
                throw new LocalizedException(__('The credit card has been stored already.') );
            }
            else {
                $foundPaymentToken->setIsActive(true);
                $this->paymentTokenRepository->save($foundPaymentToken);
            }
        }

        // Otherwise save the card
        else {
            $gatewayToken = $this->authorizedResponse['card']['id'];

            $paymentToken->setGatewayToken($gatewayToken);
            $paymentToken->setIsVisible(true);

            $this->paymentTokenRepository->save($paymentToken);
        }
    }

    /**
     * Runs the authorization command for the gateway.
     *
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    private function authorizeTransaction() {

        $requestUri = 'charges/token'; // todo - get this url from http client class

        $transfer = $this->transferFactory->create([
            'autoCapture'   => 'N',
            'description'   => 'Saving new card',
            'value'         => (float) $this->scopeConfig->getValue('payment/checkout_com/save_card_check_amount') * 100,
            'currency'      => $this->scopeConfig->getValue('payment/checkout_com/save_card_check_currency'),
            'cardToken'     => $this->cardToken,
            'email'         => $this->customerEmail,
        ]);
        
        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => $requestUri,
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient($requestUri, $transfer)->request();
            
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

            // Outpout the response in debug mode
            $this->watchdog->bark($result);


            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }

            $this->authorizedResponse = $result;
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
        finally {
            $this->logger->debug($log);
        }
    }

    /**
     * Validates the authorization response.
     *
     * @throws LocalizedException
     */
    private function validateAuthorization() {
        if( array_key_exists('status', $this->authorizedResponse) AND $this->authorizedResponse['status'] === 'Declined') {
            throw new LocalizedException(__('The transaction has been declined.'));
        }
    }

    /**
     * Runs the void command for the gateway.
     *
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    private function voidTransaction() {
        $transactionId  = $this->authorizedResponse['id'];
        $transfer       = $this->transferFactory->create([
            'trackId'   => ''
        ]);

        $chargeUrl = 'charges/' . $transactionId . '/void';

        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => $chargeUrl,
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient($chargeUrl, $transfer)->request();
           
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

            if( array_key_exists('errorCode', $result) ) {
                throw new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);
            }
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
        finally {
            $this->logger->debug($log);
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
     * Returns the payment token instance if exists.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface|null
     */
    private function foundExistedPaymentToken(PaymentTokenInterface $paymentToken) {
        return $this->paymentTokenManagement->getByPublicHash( $paymentToken->getPublicHash(), $paymentToken->getCustomerId() );
    }

}
