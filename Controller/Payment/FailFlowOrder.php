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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use CheckoutCom\Magento2\Provider\OrderSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class FailFlowOrder extends Action
{
    private ApiHandlerService $apiHandler;
    private Logger $logger;
    private OrderHandlerService $orderHandler;
    private OrderSettings $orderSettings;
    private OrderStatusHandlerService $orderStatusHandler;
    private PaymentErrorHandlerService $paymentErrorHandlerService;
    private Session $session;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ApiHandlerService $apiHandler,
        Context $context,
        Logger $logger,
        ManagerInterface $messageManager,
        OrderHandlerService $orderHandler,
        OrderSettings $orderSettings,
        OrderStatusHandlerService $orderStatusHandler,
        PaymentErrorHandlerService $paymentErrorHandlerService,
        Session $session,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->orderHandler = $orderHandler;
        $this->orderSettings = $orderSettings;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
        $this->session = $session;
        $this->storeManager = $storeManager;
    }

    public function execute(): ResponseInterface
    {
        try {
            // Retrive data from parameters
            $sessionId = $this->getRequest()->getParam('cko-session-id', null);

            $reference = $this->getRequest()->getParam('reference', null);

            if (empty($reference) && empty($sessionId)) {
                $this->messageManager->addErrorMessage(
                   __('The transaction could not be processed.')
                );

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }

            // Get data from API
            $apiCallResponse = [];

            $storeCode = $this->storeManager->getStore()->getCode();

            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            $apiCallResponse = $sessionId ? $api->getDetailsFromSessionId($sessionId) : $api->getDetailsFromReference($reference);

            // Case save card
            if (isset($apiCallResponse['isSaveCard']) && $apiCallResponse['isSaveCard']) {
                $this->messageManager->addErrorMessage(
                    __('The card could not be saved.')
                );

                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }
            
            if (empty($apiCallResponse) || !isset($apiCallResponse['response']) || !$api->isValidResponse($apiCallResponse['response'])) {
                $this->session->restoreQuote();

                $this->messageManager->addErrorMessage(
                    __('The transaction could not be processed.')
                );

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }

            $response = $apiCallResponse['response'];

            $this->logger->display($response);

            // Action on orders

            $order = $this->orderHandler->getOrder([
                'increment_id' => $apiCallResponse['orderId'],
            ]);

            if (!$this->orderHandler->isOrder($order)) {
                $this->messageManager->addErrorMessage(
                    __('The transaction could not be processed.')
                );

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }

            // All checks succeed
            // Continue with actions

            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $action = $this->orderSettings->getActionOnFailedPayment($websiteCode);
            $status = $action === 'cancel' ? 'canceled' : 'false';

            $this->paymentErrorHandlerService->logPaymentError(
                $response,
                $order,
                $status
            );

            $this->session->restoreQuote();
            $this->orderStatusHandler->handleFailedPayment($order);
            $this->orderHandler->deleteOrder($order);

            $errorMessage = null;
            $type = $response['source']['type'] ?? '';

            if (isset($response['actions'][0]['response_code']) && $type !== 'knet') {
                $errorMessage = $this->paymentErrorHandlerService->getErrorMessage(
                    $response['actions'][0]['response_code']
                );
            }
            
            $this->messageManager->addErrorMessage(
                $errorMessage ? $errorMessage->getText() : __('The transaction could not be processed.')
            );

            // Redirect

            if (isset($response['metadata']['failureUrl'])) {
                return $this->_redirect($response['metadata']['failureUrl']);
            }

            return $this->_redirect('checkout/cart', ['_secure' => true]);

        } catch (Exception $e) {
            $this->session->restoreQuote();
            $this->messageManager->addErrorMessage(
                __('The transaction could not be processed.')
            );

            $this->logger->display(sprintf('The transaction could not be processed: %s', $e->getMessage()));
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
