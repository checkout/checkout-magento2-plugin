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
        \CheckoutCom\Magento2\Helper\Logger::write($this->data);
        $this->methodId = $this->data['methodId'];
        $this->cardToken = $this->getRequest()->getParam('cardToken');
    }

    /**
     * Handles the controller method.
     */
    public function execute() {


return $this->execute2();



        $url = false;
        $message = '';
        $success = false;
        if ($this->getRequest()->isAjax()) {
            try {
                if ($this->quote) {

                    // Send the charge request
                    $response = $this->methodHandler->get($this->methodId)
                        ->sendPaymentRequest(
                            $this->data,
                            $this->quote->getGrandTotal(),
                            $this->quote->getQuoteCurrencyCode(),
                            $this->quoteHandler->getReference($this->quote)
                        );

                    // Process the response
                    $success = $this->apiHandler->isValidResponse($response); //@todo: remove this?




                    // Pending -> further action needed.










                    // Handle 3DS cases
                    $redirectionUrl = $response->getRedirection();
                    if ($success && !empty($redirectionUrl)) {
                        $url = $redirectionUrl;
                    }

                    // Handle the order placement
                    else if ($success && empty($redirectionUrl)) {
                        $order = $this->placeOrder((array) $response);
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
            'message' => $message,
            'url' => $url
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












    protected function execute2() {

        $url = '';
        $message = __('Invalid request.');
        $success = false;

        if ($this->getRequest()->isAjax() && $this->quote) {

            $response = $this->requestPayment();
            if($success = $response->isSuccessful()) { // Payment requested successfully

                if ($response->isPending()) { // Further action needed
                    $url = $response->getRedirection();
\CheckoutCom\Magento2\Helper\Logger::write('PlaceOrder: ' . $url);
                } else {
                    $order = $this->placeOrder((array) $response); // What to do from here?
                }






            } else { // Payment failed
                $success = false;
                $message = __('The transaction could not be processed.');
            }

        }

        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
            'url' => $url
        ]);

    }



    protected function requestPayment() {

        // Send the charge request
        return $this->methodHandler->get($this->methodId)
                                    ->sendPaymentRequest($this->data,
                                                         $this->quote->getGrandTotal(),
                                                         $this->quote->getQuoteCurrencyCode(),
                                                         $this->quoteHandler->getReference($this->quote));


    }

































}
