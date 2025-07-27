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

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Fail
 */
class Fail extends Action
{
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        private TransactionHandlerService $transactionHandler,
        private StoreManagerInterface $storeManager,
        private ApiHandlerService $apiHandler,
        private OrderHandlerService $orderHandler,
        private OrderStatusHandlerService $orderStatusHandler,
        private Logger $logger,
        private PaymentErrorHandlerService $paymentErrorHandlerService,
        private Config $config,
        private Session $session
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
    }

    /**
     * Handles the controller method
     *
     * @return ResponseInterface
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
                $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

                // Get the payment details
                $response = $api->getPaymentDetails($sessionId);

                // Logging
                $this->logger->display($response);

                // Don't restore quote if saved card request
                if ($response['amount'] !== 0 && $response['amount'] !== 100) {
                    // Find the order from increment id
                    $order = $this->orderHandler->getOrder([
                        'increment_id' => $response['reference'],
                    ]);

                    $storeCode = $this->storeManager->getStore()->getCode();
                    $action = $this->config->getValue('order_action_failed_payment', null, $storeCode);
                    $status = $action === 'cancel' ? 'canceled' : 'false';

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

                    //Delete order if payment first
                    $this->orderHandler->deleteOrder($order);

                    $errorMessage = null;
                    if (isset($response['actions'][0]['response_code'])) {
                        $errorMessage = $this->paymentErrorHandlerService->getErrorMessage(
                            $response['actions'][0]['response_code']
                        );
                    }

                    if ($response['source']['type'] === 'knet') {
                        $amount = $this->transactionHandler->amountFromGateway(
                            $response['amount'] ?? null,
                            $order
                        );

                        // Display error message
                        $this->messageManager->addErrorMessage(
                            __('The transaction could not be processed.')
                        );
                    } else {
                        $this->messageManager->addErrorMessage(
                            $errorMessage ? $errorMessage->getText() : __('The transaction could not be processed.')
                        );
                    }

                    // Return to the cart
                    if (isset($response['metadata']['failureUrl'])) {
                        return $this->_redirect($response['metadata']['failureUrl']);
                    }

                    return $this->_redirect('checkout/cart', ['_secure' => true]);
                }

                $this->messageManager->addErrorMessage(
                    __('The card could not be saved.')
                );

                // Return to the saved card page
                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }
        } catch (Exception $e) {
            // Restore the quote
            $this->session->restoreQuote();

            $this->messageManager->addErrorMessage(
                __('The transaction could not be processed.')
            );

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
