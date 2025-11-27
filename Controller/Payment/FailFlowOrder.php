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

/**
 * Class Fail
 */
class FailFlowOrder extends Action
{
    private StoreManagerInterface $storeManager;
    private ApiHandlerService $apiHandler;
    private OrderSettings $orderSettings;
    private OrderHandlerService $orderHandler;
    private OrderStatusHandlerService $orderStatusHandler;
    private Logger $logger;
    private PaymentErrorHandlerService $paymentErrorHandlerService;
    private Session $session;

    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        OrderSettings $orderSettings,
        OrderHandlerService $orderHandler,
        OrderStatusHandlerService $orderStatusHandler,
        Logger $logger,
        PaymentErrorHandlerService $paymentErrorHandlerService,
        Session $session
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->logger = $logger;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
        $this->session = $session;
        $this->orderSettings = $orderSettings;
    }

    /**
     * Handles the controller method
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            // Retrive data from parameters
            $sessionId = $this->getRequest()->getParam('cko-session-id', null);

            $reference = $this->getRequest()->getParam('reference', null);

            if (empty($reference) && empty($sessionId)) {
                $this->messageManager->addErrorMessage(
                    __('Invalid request. No session ID or reference found.')
                );

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }

            // Get data from API
            $apiCallResponse = [];

            $storeCode = $this->storeManager->getStore()->getCode();

            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            $apiCallResponse = $sessionId ? $api->getDetailsFromSessionId($sessionId) : $api->getDetailsFromReference($reference);

            // Case save card
            if($apiCallResponse['isSaveCard']) {
                $this->messageManager->addErrorMessage(
                    __('The card could not be saved.')
                );

                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }
            
            if (empty($apiCallResponse) || !$api->isValidResponse($apiCallResponse['response'])) {
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
                    __('Invalid request. No order found.')
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
            if (isset($response['actions'][0]['response_code']) && $response['source']['type'] !== 'knet') {
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
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
