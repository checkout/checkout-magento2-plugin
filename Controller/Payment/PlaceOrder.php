<?php

namespace CheckoutCom\Magento2\Controller\Payment;

class PlaceOrder extends \Magento\Framework\App\Action\Action {
    
    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var MethodHandlerService
     */
    protected $methodHandler;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;
     
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var String
     */
    protected $cardToken;

    /**
     * @var Quote
     */
    protected $quote;

	/**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\MethodHandlerService $methodHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->methodId = $this->getRequest()->getParam('methodId');
        $this->cardToken = $this->getRequest()->getParam('cardToken');
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        $message = '';
        $success = false;
        if ($this->getRequest()->isAjax()) {
            try {
                if ($this->quote) {
                    // Send the charge request
                    $response = $this->methodHandler->get($this->methodId)
                        ->sendPaymentRequest(
                            $this->cardToken, 
                            $this->quote->getGrandTotal(),
                            $this->quote->getQuoteCurrencyCode(),
                            $this->quoteHandler->getReference($this->quote)
                        );

                    // Process the response
                    $success = $response->isSuccessful();

                    // Handle 3DS cases
                    $redirectionUrl = $response->getRedirection();
                    if ($success && !empty($redirectionUrl)) {
                        $message = $redirectionUrl;
                    }

                    // Handle the order placement
                    else if ($success && empty($redirectionUrl)) {
                        $order = $this->placeOrder($response);
                    }

                    if (!($success && $this->orderHandler->isOrder($order))) {
                        $message = __('The transaction could not be processed.');
                    }
                }
            }
            catch(\Exception $e) {
                $message = __($e->getMessage());
            }   
        }
        else {
            $message = __('Invalid request.');
        }

        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * Handles the order placing process.
     */
    protected function placeOrder($response = null) {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler->setMethodId($this->methodId)
                ->setPaymentData($response)
                ->placeOrder($reservedIncrementId);

            return $order;
        }
        catch(\Exception $e) {
            return false;
        }   
    }
}
