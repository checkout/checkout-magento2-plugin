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
 * Class Verify
 */
class Verify extends \Magento\Framework\App\Action\Action
{
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var CheckoutApi
     */
    public $apiHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Verify constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    )
    {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->quoteHandler = $quoteHandler;
        $this->vaultHandler = $vaultHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Get the session id
        $sessionId = $this->getRequest()->getParam('cko-session-id', null);
        if ($sessionId) {
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Get the payment details
            $response = $api->getPaymentDetails($sessionId);

            // Set the method ID
            $this->methodId = $response->metadata['methodId'];

            // Check for zero dollar auth
            if ($response->amount !== 0) {
                // Find the order from increment id
                $order = $this->orderHandler->getOrder([
                    'increment_id' => $response->reference
                ]);

                // Process the order
                if ($this->orderHandler->isOrder($order)) {
                    // Add the payment info to the order
                    $order = $this->utilities->setPaymentData($order, $response);

                    // Save the order
                    $order->save();

                    // Logging
                    $this->logger->display($response);

                    // Process the response
                    if ($api->isValidResponse($response)) {
                        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                    } else {
                        // Restore the quote
                        $this->quoteHandler->restoreQuote($response->reference);

                        // Add and error message
                        $this->messageManager->addErrorMessage(
                            __('The transaction could not be processed or has been cancelled.')
                        );
                    }
                } else {
                    // Add and error message
                    $this->messageManager->addErrorMessage(
                        __('Invalid request. No order found.')
                    );
                }
            } else {
                // Save the card
                $this->saveCard($response);

                // Redirect to the
                return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
            }
        } else {
            // Add and error message
            $this->messageManager->addErrorMessage(
                __('Invalid request. No session ID found.')
            );
        }

        // Return to the cart
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    public function saveCard($response) {
        // Get the customer
        $customer = $this->shopperHandler->getCustomerData(
            ['id' => $this->payload->data->metadata->customerId]
        );

        // Save the card
        $success = $this->vaultHandler
            ->setCardToken($this->payload->data->source->id)
            ->setCustomerId($customer->getId())
            ->setCustomerEmail($customer->getEmail())
            ->setResponse($response)
            ->saveCard();

        // Prepare the response UI message
        if ($success) {
            $this->messageManager->addSuccessMessage(
                __('The payment card has been stored successfully.')
            );
        } 
        else {
            $this->messageManager->addErrorMessage(
                __('The card could not be saved.')
            );
        }
    }
}
