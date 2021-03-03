<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Payment;

/**
 * Class Fail
 */
class Fail extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var CheckoutApi
     */
    public $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var OrderStatusHandlerService
     */
    public $orderStatusHandler;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var PaymentErrorHandlerService
     */
    public $paymentErrorHandlerService;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Session
     */
    protected $session;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService $orderStatusHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService $paymentErrorHandlerService,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \Magento\Checkout\Model\Session $session
    )
    {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->logger = $logger;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        try {
            // Get the session id
            $sessionId = $this->getRequest()->getParam('cko-session-id', null);
            if ($sessionId) {
                // Get the store code
                $storeCode = $this->storeManager->getStore()->getCode();

                // Initialize the API handler
                $api = $this->apiHandler->init($storeCode);

                // Get the payment details
                $response = $api->getPaymentDetails($sessionId);

                // Logging
                $this->logger->display($response);

                // Don't restore quote if saved card request
                if ($response->amount !== 0 && $response->amount !== 100) {
                    // Find the order from increment id
                    $order = $this->orderHandler->getOrder([
                        'increment_id' => $response->reference
                    ]);

                    $storeCode = $this->storeManager->getStore()->getCode();
                    $action = $this->config->getValue('order_action_failed_payment', null, $storeCode);
                    $status = $action == 'cancel' ? 'canceled' : false;

                    // Log the payment error
                    $this->paymentErrorHandlerService->logPaymentError(
                        $response,
                        $order,
                        $status
                    );

                    // Restore the quote
                    $this->session->restoreQuote();

                    // Handle the failed order
                    $this->orderStatusHandler->handleFailedPayment($order);

                    $errorMessage = null;
                    if (isset($response->actions[0]['response_code'])) {
                        $errorMessage = $this->paymentErrorHandlerService->getErrorMessage(
                            $response->actions[0]['response_code']
                        );
                    }

                    // Display the message
                    $this->messageManager->addErrorMessage(
                        $errorMessage ? $errorMessage->render() : __('The transaction could not be processed.')
                    );

                    // Return to the cart
                    if (isset($response->metadata['failureUrl'])) {
                        return $this->_redirect($response->metadata['failureUrl']);
                    } else {
                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The card could not be saved.')
                    );

                    // Return to the saved card page
                    return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
                }
            }
        } catch (\Checkout\Library\Exceptions\CheckoutHttpException $e) {
            $order = $this->session->getLastRealOrder();

            if ($order) {
                // Restore the quote
                $this->session->restoreQuote();

                $this->messageManager->addErrorMessage(
                    __('The 3DSecure session expired')
                );

                // Handle the failed order
                $this->orderStatusHandler->handleFailedPayment($order);

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }
        }
    }
}
