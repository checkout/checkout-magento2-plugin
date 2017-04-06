<?php

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
     * @var string
     */
    protected $customerName;

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
     * StoreCardService constructor.
     * @param Logger $logger
     * @param VaultTokenFactory $vaultTokenFactory
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Logger $logger,
        VaultTokenFactory $vaultTokenFactory,
        GatewayConfig $gatewayConfig,
        TransferFactory $transferFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ResponseFactory $responseFactory
    ) {
        $this->logger                   = $logger;
        $this->vaultTokenFactory        = $vaultTokenFactory;
        $this->gatewayConfig            = $gatewayConfig;
        $this->transferFactory          = $transferFactory;
        $this->paymentTokenRepository   = $paymentTokenRepository;
        $this->paymentTokenManagement   = $paymentTokenManagement;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Sets the customer ID.
     *
     * @param int $customerId
     * @return StoreCardService
     */
    public function setCustomerId($customerId) {
        $this->customerId = (int) $customerId;

        return $this;
    }

    /**
     * Sets the customer email address.
     *
     * @param string $customerEmail
     * @return StoreCardService
     */
    public function setCustomerEmail($customerEmail) {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    /**
     * Sets the customer name.
     *
     * @param string $customerName
     * @return StoreCardService
     */
    public function setCustomerName($customerName) {
        $this->customerName = substr($customerName, 0, 100);

        return $this;
    }

    /**
     * Sets the card token and the data.
     *
     * @param string $cardToken
     * @param array $cardData
     * @return StoreCardService
     */
    public function setCardTokenAndData($cardToken, array $cardData) {
        $this->cardToken    = $cardToken;
        $this->cardData     = $cardData;

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
        $paymentToken       = $this->vaultTokenFactory->create($this->cardData, $this->customerId);
        $foundPaymentToken  = $this->foundExistedPaymentToken($paymentToken);
        
        if($foundPaymentToken) {
            if($foundPaymentToken->getIsActive()) {
                throw new LocalizedException(__('The credit card has been stored already.') );
            }
            else {
                $foundPaymentToken->setIsActive(true);
                $this->paymentTokenRepository->save($foundPaymentToken);
            }
        }
        else {
            $this->authorizeTransaction();
            $this->validateAuthorization();
            
            if(isset($this->authorizedResponse['redirectUrl'])){
                $this->responseFactory->create()->setRedirect( $this->authorizedResponse['redirectUrl'] )->sendResponse();
                exit;
            }
            
            $this->voidTransaction();

            $paymentToken->setGatewayToken($this->authorizedResponse['card']['id']);
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
        $transfer = $this->transferFactory->create([
            'autoCapture'   => 'N',
            'description'   => 'Saving new card',
            'value'         => 1,
            'currency'      => 'USD',
            'cardToken'     => $this->cardToken,
            'email'         => $this->customerEmail,
            'customerName'  => $this->customerName,
        ]);
        
        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => 'charges/token',
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient('charges/token', $transfer)->request();
            
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

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
        $transfer       = $this->transferFactory->create([]);

        $log = [
            'request'           => $transfer->getBody(),
            'request_uri'       => 'charges/' . $transactionId . '/void',
            'request_headers'   => $transfer->getHeaders(),
            'request_method'    => 'POST',
        ];

        try {
            $response           = $this->getHttpClient('charges/' . $transactionId . '/void', $transfer)->request();
           
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
