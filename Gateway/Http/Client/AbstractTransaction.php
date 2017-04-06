<?php

namespace CheckoutCom\Magento2\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use CheckoutCom\Magento2\Gateway\Exception\ApiClientException;
use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use Zend_Http_Client_Exception;

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
     * AbstractTransaction constructor.
     * @param Logger $logger
     * @param ZendClient $clientFactory
     * @param ManagerInterface $messageManager
     * @param GatewayResponseHolder $gatewayResponseHolder
     */
    public function __construct(Logger $logger, ZendClient $clientFactory, ManagerInterface $messageManager, GatewayResponseHolder $gatewayResponseHolder) {
        $this->logger                   = $logger;
        $this->clientFactory            = $clientFactory;
        $this->messageManager           = $messageManager;
        $this->gatewayResponseHolder    = $gatewayResponseHolder;
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
        
        if($this->gatewayResponseHolder->hasCallbackResponse()) {
            $response = $this->gatewayResponseHolder->getGatewayResponse();

            $this->logger->debug([
                'action'    => 'callback',
                'response'  => $response,
            ]);

            return $response;
        }

        $this->prepareTransfer($transferObject);

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
