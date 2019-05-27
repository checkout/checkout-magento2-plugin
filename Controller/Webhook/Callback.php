<?php

namespace CheckoutCom\Magento2\Controller\Webhook;

use Magento\Sales\Model\Order\Payment\Transaction;

class Callback extends \Magento\Framework\App\Action\Action {
    /**
     * @var array
     */
    protected static $transactionMapper = [
        'Authorization' => Transaction::TYPE_AUTH,
        'Capture' => Transaction::TYPE_CAPTURE,
        'Refund' => Transaction::TYPE_REFUND,
        'Void' => Transaction::TYPE_VOID
    ];
    
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

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
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var Config
     */
    protected $config;

	/**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Model\Service\apiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->quoteHandler = $quoteHandler;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        try {
            if ($this->config->isValidAuth()) {
                // Get the post data
                $payload = json_decode($this->getRequest()->getContent());
                if (isset($payload->data->id)) {
                    // Get the payment details
                    $response = $this->apiHandler->getPaymentDetails($payload->data->id);
                    if ($this->apiHandler->isValidResponse($response)) {
                        // Process the order
                        $order = $this->orderHandler->processOrder(
                            $response->reference,
                            (array) $response,
                            true
                        );

                        // Capture
                        $this->transactionHandler->createTransaction(
                            $order,
                            $this->getNeededTransaction(),
                            $response
                        );
                    }
                }
            }
   
        } catch (\Exception $e) {

        }     

        exit();
        
        return $this->jsonFactory->create()->setData([]);

    }


    /**
     * Get a transaction type from name.
     *
     * @return string
     */
    public function getNeededTransaction($name) {
        return self::$transactionMapper($name);
    }

}