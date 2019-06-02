<?php

namespace CheckoutCom\Magento2\Controller\Webhook;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;

class Callback extends \Magento\Framework\App\Action\Action {
    /**
     * @var array
     */
    protected static $transactionMapper = [
        'payment_approved' => Transaction::TYPE_AUTH,
        'payment_captured' => Transaction::TYPE_CAPTURE,
        'payment_refunded' => Transaction::TYPE_REFUND,
        'payment_voided' => Transaction::TYPE_VOID
    ];

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var apiHandler
     */
    protected $apiHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var ShopperHandlerService
     */
    protected $shopperHandler;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * @var Config
     */
    protected $config;

	/**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\ShopperHandlerService $shopperHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->orderRepository = $orderRepository;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->quoteHandler = $quoteHandler;
        $this->shopperHandler = $shopperHandler;
        $this->transactionHandler = $transactionHandler;
        $this->vaultHandler = $vaultHandler;
        $this->config = $config;

        // Set the payload data
        $this->payload = $this->getPayload();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        try {
            // Prepare the response handler
            $resultFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

            // Process the request
            if ($this->config->isValidAuth()) {
                // Process the request
                if (isset($this->payload->data->id)) {
                    // Get the payment details
                    $response = $this->apiHandler->getPaymentDetails($this->payload->data->id);
                    if ($this->apiHandler->isValidResponse($response)) {

                        // Handle the save card request
                        if ($this->cardNeedsSaving()) {
                            $this->saveCard($response);
                        }

                        // Process the order
                        $order = $this->orderHandler
                            ->setMethodId($this->payload->data->metadata->methodId)
                            ->handleOrder(
                                $response->reference,
                                true
                            );

                        if ($this->orderHandler->isOrder($order)) {
                            // Handle the transaction
                            $this->transactionHandler->createTransaction(
                                $order,
                                static::$transactionMapper[$this->payload->type],
                                $this->payload
                            );

                            // Save the order
                            $order = $this->orderRepository->save($order);
                        }
                    }
                }

                // Set a valid response
                $resultFactory->setHttpResponseCode(WebResponse::HTTP_OK);
            }
            else  {
                $resultFactory->setHttpResponseCode(WebException::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            $resultFactory->setHttpResponseCode(WebException::HTTP_INTERNAL_ERROR);
            $resultFactory->setData(['error_message' => $e->getMessage()]);
        }   
        
        return $resultFactory;
    }

    protected function getPayload() {
        return json_decode($this->getRequest()->getContent());
    }

    protected function cardNeedsSaving() {
        return isset($this->payload->data->metadata->saveCard)
        && $this->payload->data->metadata->saveCard
        && isset($this->payload->data->metadata->customerId)
        && (int) $this->payload->data->metadata->customerId > 0
        && isset($this->payload->data->source->id)
        && !empty($this->payload->data->source->id);
    }

    protected function saveCard($response) {
        try {
            // Get the customer
            $customer = $this->shopperHandler->getCustomerData(
                ['id' => $this->payload->data->metadata->customerId]
            );

            // Save the card
            $success = $this->vaultHandler
            ->setCardToken($this->payload->data->source->id)
            ->setCustomerId($customer->getId())
            ->setCustomerEmail($customer->getEmail())
            ->setResponse($response)
            ->saveCard();
    
        } catch (\Exception $e) {


        }     
    }
}