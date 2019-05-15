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
     * @var ApiHandlerService
     */
    protected $apiHandler;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var String
     */
    protected $cardToken;

    /**
     * @var Quote
     */
    protected $quote;


    /**
     * Magic Methods
     */

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
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->methodHandler = $methodHandler;
        $this->apiHandler = $apiHandler;
        $this->checkoutSession = $checkoutSession;
        $this->utilities = $utilities;
        $this->config = $config;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Set some required properties
        $this->data = $this->getRequest()->getParams();
        $this->methodId = $this->data['methodId'];
    }


    /**
     * Methods
     */

    /**
     * Main controller function.
     *
     * @return     JSON
     */
    public function execute() {

        $url = '';
        $message = __('Invalid request.');
        $success = false;

        if ($this->getRequest()->isAjax() && $this->quote) {

            $response = $this->requestPayment();
            if($response && $success = $response->isSuccessful()) { // Payment requested successfully

                if ($response->isPending()) { // Further action needed
                    $url = $response->getRedirection();
                } else {

                    if(!$this->placeOrder((array) $response)) {
                        // refund or void accordingly
\CheckoutCom\Magento2\Helper\Logger::write('PlaceOrder->execute: should refund');
                    }

                }

                $message = '';

            } else { // Payment failed
                $success = false;
                $message = __('The transaction could not be processed. Review payment method\'s conditions.');
            }

        }

        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
            'url' => $url
        ]);

    }

    /**
     * Request payment to API handler.
     *
     * @return     Response
     */
    protected function requestPayment() {

        // Send the charge request
        return $this->methodHandler->get($this->methodId)
                                    ->sendPaymentRequest($this->data,
                                                         $this->quote->getGrandTotal(),
                                                         $this->quote->getQuoteCurrencyCode(),
                                                         $this->quoteHandler->getReference($this->quote));


    }

    /**
     * Handles the order placing process.
     *
     * @param      array    $response  The response
     *
     * @return     mixed
     */
    protected function placeOrder(array $response = null) {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->methodId)
                ->placeOrder($reservedIncrementId, $response);

            // Add the payment info to the order
            $order = $this->utilities
                ->setPaymentData($order, $response);

            return $order;
        }
        catch(\Exception $e) {
            return false;
        }
    }
}
