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
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class VerifyFlowOrder extends AbstractPayment
{
    protected FlowGeneralSettings $flowGeneralConfig;
    protected OrderRepositoryInterface $orderRepository;
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
        parent::__construct(
            $apiHandler,
            $context,
            $logger,
            $messageManager,
            $orderHandler,
            $session,
            $storeManager,
        );

        $this->flowGeneralConfig = $flowGeneralConfig;
        $this->orderRepository = $orderRepository;
        $this->transactionHandler = $transactionHandler;
        $this->utilities = $utilities;
        $this->vaultHandler = $vaultHandler;
    }

    protected function paymentAction(array $apiCallResponse, OrderInterface $order): ResponseInterface
    {
        $this->logger->display($apiCallResponse['response']);

        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();

            if ($this->flowGeneralConfig->useFlow($websiteCode)) {
                $order = $this->utilities->setPaymentData($order, $apiCallResponse['response']);
                $this->orderRepository->save($order);
            }
        } catch (Exception $e) {
            $this->logger->write($e->getMessage());
        }

        if (isset($apiCallResponse['response']['metadata']['successUrl']) &&
            false === strpos(
                $apiCallResponse['response']['metadata']['successUrl'],
                'checkout_com/payment/verify'
            )
        ) {
            return $this->_redirect($apiCallResponse['response']['metadata']['successUrl']);
        }

        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
    }

    /**
     * @throws Exception
     */
    protected function saveCardAction(array $apiCallResponse): ResponseInterface
    {
        if (!isset($apiCallResponse['response'])) {
            $this->messageManager->addErrorMessage(
                __('The card could not be saved.')
            );

            return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
        }
        
        $response = $apiCallResponse['response'];

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

        return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
    }
}
