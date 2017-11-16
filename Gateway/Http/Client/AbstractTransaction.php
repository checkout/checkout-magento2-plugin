<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Http\Client;

use Zend_Http_Client_Exception;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use CheckoutCom\Magento2\Gateway\Exception\ApiClientException;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

abstract class AbstractTransaction implements ClientInterface {

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ZendClient
     */
    protected $clientFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var array
     */
    protected $body = [];

    /**
     * @var string
     */
    protected $fullUri;

    /**
     * @var GatewayResponseHolder
     */
    protected $gatewayResponseHolder;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * AbstractTransaction constructor.
     * @param Logger $logger
     * @param ZendClient $clientFactory
     * @param ManagerInterface $messageManager
     * @param GatewayResponseHolder $gatewayResponseHolder
     */
    public function __construct(
        Logger $logger,
        ZendClient $clientFactory,
        ManagerInterface $messageManager,
        GatewayResponseHolder $gatewayResponseHolder,
        Cart $cart,
        CartManagementInterface $cartManagement,
        QuoteManagement $quoteManagement,
        CustomerSession $customerSession
    ) {
        $this->logger                   = $logger;
        $this->clientFactory            = $clientFactory;
        $this->messageManager           = $messageManager;
        $this->gatewayResponseHolder    = $gatewayResponseHolder;
        $this->cart                     = $cart;
        $this->cartManagement           = $cartManagement;
        $this->quoteManagement          = $quoteManagement;
        $this->customerSession          = $customerSession;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws ClientException
     * @throws ApiClientException
     * @throws Zend_Http_Client_Exception
     * @throws \LogicException
     */
    public function placeRequest(TransferInterface $transferObject) {
        
        if ($this->gatewayResponseHolder->hasCallbackResponse()) {
            $response = $this->gatewayResponseHolder->getGatewayResponse();

            $this->logger->debug([
                'action'    => 'callback',
                'response'  => $response,
            ]);

            return $response;
        }

        // Prepare the transfert data
        $this->prepareTransfer($transferObject);

        // Update the email field for guest users
        $this->updateGuestEmail($this->body);

        // Prepare some log data
        $log = [
            'request'           => $this->body,
            'request_uri'       => $this->fullUri,
            'request_headers'   => $transferObject->getHeaders(),
            'request_method'    => $this->getMethod(),
        ];

        $result = [];

        $client = $this->clientFactory;
        $client->setConfig($transferObject->getClientConfig());
        
        $client->setMethod($this->getMethod());
       
        switch($this->getMethod()) {
            case \Zend_Http_Client::GET:
                $client->setRawData( json_encode($this->body) ) ;
                break;
            case \Zend_Http_Client::POST:
                $client->setRawData( json_encode($this->body) ) ;
                break;
            default:
                throw new \LogicException( sprintf('Unsupported HTTP method %s', $transferObject->getMethod()) );
        }

        $client->setHeaders($transferObject->getHeaders());
        $client->setUrlEncodeBody($transferObject->shouldEncode());
           
        $client->setUri($this->fullUri);
        
        try {
            $response           = $client->request();
            
            $result             = json_decode($response->getBody(), true);
            $log['response']    = $result;

            if( array_key_exists('errorCode', $result) ) {
                $exception = new ApiClientException($result['message'], $result['errorCode'], $result['eventId']);

                $this->messageManager->addErrorMessage( $exception->getFullMessage() );

                throw $exception;
            }
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
        finally {
            $this->logger->debug($log);
        }

        return $result;
    }

    /**
     * Adds the shopper email if not available in the request.
     * The shopper email will be missing (null) in case of guest checkout.
     *
     * @param TransferInterface $transferObject
     */
    protected function updateGuestEmail($requestBody) {

        if (isset($requestBody['email']) && (empty($requestBody['email']) || !$requestBody['email'])) {
            $requestBody['email'] = $this->customerSession->getData('checkoutSessionData')['customerEmail'];
        }

        return $requestBody;
    }

    /**
     * Prepares the URI and body based on the given transfer object.
     *
     * @param TransferInterface $transferObject
     */
    protected function prepareTransfer(TransferInterface $transferObject) {
        $uri    = $this->getUri();
        $body   = $transferObject->getBody();

        if( array_key_exists('chargeId', $body) ) {
            $uri = str_replace('{chargeId}', $body['chargeId'], $uri);

            unset($body['chargeId']);
        }

        $this->fullUri  = $transferObject->getUri() . $uri;
        $this->body     = $body;
    }

    /**
     * Returns the HTTP method.
     *
     * @return string
     */
    public abstract function getMethod();

    /**
     * Returns the URI.
     *
     * @return string
     */
    public abstract function getUri();

}
