<?php

namespace CheckoutCom\Magento2\Model;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use CheckoutCom\Magento2\Api\MobilePaymentInterface;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Model\Product;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use CheckoutCom\Magento2\Model\Service\OrderService;
use Magento\Quote\Model\Quote;

/**
 * Defines the implementaton class of the charge through API.
 */
class MobilePayment implements MobilePaymentInterface
{
 
    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    protected $customerRepository;
    protected $productManager;
    protected $data;
    protected $customer;

    protected $quoteFactory;
    protected $storeManager;
    protected $orderService;



    /**
     * MobilePayment Model constructor.
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
    */
    public function __construct(GatewayConfig $gatewayConfig, TransferFactory $transferFactory, CustomerRepositoryInterface $customerRepository, Product $productManager, QuoteFactory $quoteFactory, StoreManagerInterface $storeManager, OrderService $orderService) {
        $this->gatewayConfig    = $gatewayConfig;
        $this->transferFactory  = $transferFactory;
        $this->customerRepository  = $customerRepository;
        $this->productManager  = $productManager;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->orderService     = $orderService;
    }

    /**
     * Perfom a charge given the required parameters.
     *
     * @api
     * @param mixed $data.
     * @return array.
     */
    public function charge($data) {


        /*
        PHP
        array (

            'cardToken' => 'card_tok_CB9C10E3-24CC-4A82-B50A-4DEFDCB15580',
            'email' => 'david.fiaty@checkout.com',
            'value' => 110,
            'currency' => 'EUR',
            'products' => array (
                array(
                    'id' => 1,
                    'quantity' => 3,
                    'options' => array(3, 6)
                ),

                array(
                    'id' => 2,
                    'quantity' => 10,
                    'options' => array(3, 6)
                ),   

                array(
                    'id' => 4,
                    'quantity' => 10,
                    'options' => array(3, 6)
                ),               
            )
        );

        JSON
       {"cardToken":"card_tok_CB9C10E3-24CC-4A82-B50A-4DEFDCB15580","email":"david.fiaty@checkout.com","value":110,"currency":"EUR","products":[{"id":1,"quantity":3,"options":[3,6]},{"id":2,"quantity":10,"options":[3,6]},{"id":4,"quantity":10,"options":[3,6]}]}

        */

        // JSON post data to object
        $this->data = json_decode($data);

        // Load the customer from email
        $this->customer = $this->customerRepository->get(filter_var($this->data->email, FILTER_SANITIZE_EMAIL));    

        // If customer exists and amount is valid
        if ( (int) $this->customer->getId() > 0 && (float) $this->data->value > 0)  {

            // Prepare the product list
            if ( isset($this->data->products) && is_array($this->data->products) && count($this->data->products) > 0 )  {

                // Submit to gateway
                $this->submitRequestToShop();

                // Submit to gateway
                $this->submitRequestToGateway();
            }
        }
    }

    private function submitRequestToShop () {

        //init the quote
        $quote = $this->quoteFactory->create();
        $quote->setStore($this->storeManager->getStore());

        // Assign customer and currency
        $quote->setCurrency();
        $quote->assignCustomer($this->customer);
 
        // Prepare the products for shop order submission
        $quote = $this->prepareProductsForShop($quote);

        // Place the order
        $agreement = [];
        $this->orderService->execute($quote, $this->data->cardToken, $agreement);
    }

    private function submitRequestToGateway () {

        // Prepare the products for gateway submission
        $products = $this->prepareProductsForGateway();

        // Prepare the transfer data
        $transfer = $this->transferFactory->create([
            'cardToken'   => filter_var($this->data->cardToken, FILTER_SANITIZE_STRING),
            'email' => filter_var($this->data->email, FILTER_SANITIZE_EMAIL),
            'value' => filter_var($this->data->value, FILTER_SANITIZE_NUMBER_FLOAT),
            'currency' => filter_var($this->data->currency, FILTER_SANITIZE_STRING),
            'chargeMode' => 1,
            'autoCapture' => $this->gatewayConfig->isAutoCapture() ? 'Y' : 'N',
            'autoCapTime' => $this->gatewayConfig->getAutoCaptureTimeInHours(),
            'products' => $products
        ]); 

        // Try a charge
        try {
            // Query the gateway
            $response = $this->getHttpClient('charges/token', $transfer)->request();
                
            // Return the response
            echo $response->getBody();

            // Exit for JSON output
            exit(0);
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }
    }

    private function prepareProductsForShop(Quote $quote) {

        // Loop through the products array
        foreach ($this->data->products as $product) {

            // If the product id > 0
            if ((int) $product->id > 0) {

                // Load the product object
                $p = $this->productManager->load($product->id);

                // Add to quote
                $quote->addProduct($p, intval($product->quantity));
            }
        }

        // Return the quote
        return $quote;
    }

    private function prepareProductsForGateway() {
        // Prepare the output array
        $output = array();

        // Loop through the products array
        foreach ($this->data->products as $product) {

            // If the product id > 0
            if ((int) $product->id > 0) {

                // Load the product object
                $p = $this->productManager->load($product->id);

                // Feed the output array
                $output[] = array(
                    'name' => $p->getName(),
                    'price' => number_format($p->getPrice(), 2),
                    'quantity' => (int) $product->quantity,
                    //'description' => $p->getData('description'),
                    //'image' => null,
                );
            }
        }

        // Return the array
        return $output;
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
}