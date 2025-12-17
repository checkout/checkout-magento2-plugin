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
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class FailFlowOrder extends AbstractPayment
{
    private OrderSettings $orderSettings;
    private OrderStatusHandlerService $orderStatusHandler;
    private PaymentErrorHandlerService $paymentErrorHandlerService;

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
        parent::__construct(
            $apiHandler,
            $context,
            $logger,
            $messageManager,
            $orderHandler,
            $session,
            $storeManager,
        );

        $this->orderSettings = $orderSettings;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->paymentErrorHandlerService = $paymentErrorHandlerService;
    }

    protected function paymentAction(array $apiCallResponse, OrderInterface $order): ResponseInterface
    {
        $response = $apiCallResponse['response'];

        $this->logger->display($response);

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

        if (isset($response['metadata']['failureUrl'])) {
            return $this->_redirect($response['metadata']['failureUrl']);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    protected function saveCardAction(array $apiCallResponse): ResponseInterface
    {
        $this->messageManager->addErrorMessage(
            __('The card could not be saved.')
        );

        return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
    }
}
