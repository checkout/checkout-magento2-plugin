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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use Checkout\CheckoutApi;
use Checkout\Library\Exceptions\CheckoutHttpException;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Fail
 */
class Fail extends Action
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    protected $messageManager;
    /**
     * $transactionHandler field
     *
     * @var TransactionHandlerService $transactionHandler
     */
    private $transactionHandler;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $apiHandler field
     *
     * @var CheckoutApi $apiHandler
     */
    private $apiHandler;
    /**
     * $orderHandler field
     *
     * @var OrderHandlerService $orderHandler
     */
    private $orderHandler;
    /**
     * $orderStatusHandler field
     *
     * @var OrderStatusHandlerService $orderStatusHandler
     */
    private $orderStatusHandler;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $paymentErrorHandlerService field
     *
     * @var PaymentErrorHandlerService $paymentErrorHandlerService
     */
    private $paymentErrorHandlerService;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $session field
     *
     * @var Session $session
     */
    private $session;

    /**
     * Fail constructor
     *
     * @param Context                    $context
     * @param ManagerInterface           $messageManager
     * @param TransactionHandlerService  $transactionHandler
     * @param StoreManagerInterface      $storeManager
     * @param ApiHandlerService          $apiHandler
     * @param OrderHandlerService        $orderHandler
     * @param OrderStatusHandlerService  $orderStatusHandler
     * @param Logger                     $logger
     * @param PaymentErrorHandlerService $paymentErrorHandlerService
     * @param Config                     $config
     * @param Session                    $session
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        TransactionHandlerService $transactionHandler,
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        Logger $logger,
        PaymentErrorHandlerService $paymentErrorHandlerService,
        Config $config,
        Session $session
    ) {
        parent::__construct($context);

        $this->messageManager             = $messageManager;
        $this->storeManager               = $storeManager;
        $this->apiHandler                 = $apiHandler;
        $this->orderHandler               = $orderHandler;
        $this->orderStatusHandler         = $orderStatusHandler;
        $this->logger                     = $logger;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
        $this->config                     = $config;
        $this->session                    = $session;
        $this->transactionHandler         = $transactionHandler;
    }

    /**
     * Handles the controller method
     *
     * @return ResultInterface|ResponseInterface
     * @throws NoSuchEntityException|LocalizedException
     */
    public function execute(): ResponseInterface
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
                        'increment_id' => $response->reference,
                    ]);

                    $storeCode = $this->storeManager->getStore()->getCode();
                    $action    = $this->config->getValue('order_action_failed_payment', null, $storeCode);
                    $status    = $action === 'cancel' ? 'canceled' : false;

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

                    if ($response->source['type'] === 'knet') {
                        $amount = $this->transactionHandler->amountFromGateway(
                            $response->amount ?? null,
                            $order
                        );

                        // Display error message and knet mandate info
                        $this->messageManager->addErrorMessage(
                            __('The transaction could not be processed.')
                        );
                        $this->messageManager->addComplexNoticeMessage('knetInfoMessage', [
                            'postDate'      => $response->source['post_date'] ?? null,
                            'amount'        => $amount ?? null,
                            'paymentId'     => $response->source['knet_payment_id'] ?? null,
                            'transactionId' => $response->source['knet_transaction_id'] ?? null,
                            'authCode'      => $response->source['auth_code'] ?? null,
                            'reference'     => $response->source['bank_reference'] ?? null,
                            'resultCode'    => $response->source['knet_result'] ?? null,
                        ]);
                    } else {
                        $this->messageManager->addErrorMessage(
                            $errorMessage ? $errorMessage->getText() : __('The transaction could not be processed.')
                        );
                    }

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
        } catch (CheckoutHttpException $e) {
            // Restore the quote
            $this->session->restoreQuote();

            $this->messageManager->addErrorMessage(
                __('The transaction could not be processed.')
            );

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }
    }
}
