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

use Checkout\CheckoutApi;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class VerifyFlowOrder extends Action
{
    protected $messageManager;
    protected ApiHandlerService $apiHandler;
    protected FlowGeneralSettings $flowGeneralConfig;
    protected Logger $logger;
    protected OrderHandlerService $orderHandler;
    protected OrderRepositoryInterface $orderRepository;
    protected Session $session;
    protected StoreManagerInterface $storeManager;
    protected TransactionHandlerService $transactionHandler;
    protected Utilities $utilities;
    protected VaultHandlerService $vaultHandler;

    public function __construct(
        ApiHandlerService $apiHandler,
        Context $context,
        FlowGeneralSettings $flowGeneralConfig,
        Logger $logger,
        ManagerInterface $messageManager,
        OrderHandlerService $orderHandler,
        OrderRepositoryInterface $orderRepository,
        Session $session,
        StoreManagerInterface $storeManager,
        TransactionHandlerService $transactionHandler,
        Utilities $utilities,
        VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->flowGeneralConfig = $flowGeneralConfig;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->orderHandler = $orderHandler;
        $this->orderRepository = $orderRepository;
        $this->transactionHandler = $transactionHandler;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->utilities = $utilities;
        $this->vaultHandler = $vaultHandler;
    }

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
            $websiteCode = $this->storeManager->getWebsite()->getCode();

            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            $apiCallResponse = $sessionId ? $api->getDetailsFromSessionId($sessionId) : $api->getDetailsFromReference($reference);
            
            // Case save card
            if(isset($apiCallResponse['isSaveCard']) && $apiCallResponse['isSaveCard']) {
                $this->saveCard($apiCallResponse['response']);

                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }

            //Validate data from API

            if (empty($apiCallResponse) || !isset($apiCallResponse['response']) || !$api->isValidResponse($apiCallResponse['response'])) {
                // Restore the quote
                $this->session->restoreQuote();

                // Add and error message
                $this->messageManager->addErrorMessage(
                    __('The transaction could not be processed.')
                );

                return $this->_redirect('checkout/cart', ['_secure' => true]);
            }

            // Get order

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

            $this->logger->display($apiCallResponse['response']);

            try {
                if ($this->flowGeneralConfig->useFlow($websiteCode)) {
                    $order = $this->utilities->setPaymentData($order, $apiCallResponse['response']);
                    $this->orderRepository->save($order);
                }
            } catch (Exception $e) {
                $this->logger->write($e->getMessage());
            }

            // Redirect
            
            if (isset($apiCallResponse['response']['metadata']['successUrl']) &&
                false === strpos(
                    $apiCallResponse['response']['metadata']['successUrl'],
                    'checkout_com/payment/verify'
                )
            ) {
                return $this->_redirect($apiCallResponse['response']['metadata']['successUrl']);
            }

            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);

        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error has occurred, please select another payment method or retry in a few minutes')
            );

            $this->logger->write($e->getMessage());

        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    /**
     * @throws Exception
     */
    private function saveCard(array $response): void
    {
        // Save the card
        $success = $this->vaultHandler->setCardToken($response['source']['id'])
            ->setCustomerId()
            ->setCustomerEmail()
            ->setResponse($response)
            ->saveCard();

        // Prepare the response UI message
        if ($success) {
            $this->messageManager->addSuccessMessage(
                __('The payment card has been stored successfully.')
            );
        } else {
            $this->messageManager->addErrorMessage(
                __('The card could not be saved.')
            );
        }
    }
}
