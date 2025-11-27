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

/**
 * Class Verify
 */
class VerifyFlowOrder extends Action
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    protected $messageManager;
    private TransactionHandlerService $transactionHandler;
    private StoreManagerInterface $storeManager;
    private ApiHandlerService $apiHandler;
    private OrderHandlerService $orderHandler;
    private VaultHandlerService $vaultHandler;
    protected Logger $logger;
    protected Session $session;
    private FlowGeneralSettings $flowGeneralConfig;
    private Utilities $utilities;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        TransactionHandlerService $transactionHandler,
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        OrderHandlerService $orderHandler,
        VaultHandlerService $vaultHandler,
        Logger $logger,
        Session $session,
        FlowGeneralSettings $flowGeneralConfig,
        Utilities $utilities,
        OrderRepositoryInterface $orderRepository,
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->logger = $logger;
        $this->session = $session;
        $this->transactionHandler = $transactionHandler;
        $this->flowGeneralConfig = $flowGeneralConfig;
        $this->utilities = $utilities;
        $this->orderRepository = $orderRepository;
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
            $websiteCode = $this->storeManager->getWebsite()->getCode();

            $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

            $apiCallResponse = $sessionId ? $api->getDetailsFromSessionId($sessionId) : $api->getDetailsFromReference($reference);
            
            // Case save card
            if($apiCallResponse['isSaveCard']) {
                $this->saveCard($apiCallResponse['response']);

                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }

            //Validate data from API

            if (empty($apiCallResponse) || !$api->isValidResponse($apiCallResponse['response'])) {
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
     * Save card
     *
     * @param array $response
     *
     * @return void
     * @throws Exception
     */
    public function saveCard(array $response): void
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
