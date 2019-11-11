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

use \Checkout\Models\Payments\Refund;
use \Checkout\Models\Payments\Voids;

/**
 * Class Verify
 */
class Verify extends \Magento\Framework\App\Action\Action
{
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
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->quoteHandler = $quoteHandler;
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
            // Initialize the API handler
            $api = $this->apiHandler->init();

            // Get the payment details
            $response = $api->getPaymentDetails($sessionId);

            // Set the method ID
            $this->methodId = $response->metadata['methodId'];

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
                }
                else {
                    // Get the quote
                    $quote = $this->quoteHandler->getQuote([
                        'reserved_order_id' => $response->reference
                    ]);

                    // Restore the quote
                    $quote->setIsActive(true)->save();

                    // Add and error message
                    $this->messageManager->addErrorMessage(
                        __('The transaction could not be processed or has been cancelled.')
                    );
                }
            }
            else {
                // Add and error message
                $this->messageManager->addErrorMessage(
                    __('Invalid request. No order found.')
                );                     
            }
        }
        else {
            // Add and error message
            $this->messageManager->addErrorMessage(
                __('Invalid request. No session ID found.')
            );       
        }

        // Return to the cart
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}