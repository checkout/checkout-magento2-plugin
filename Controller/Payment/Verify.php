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
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandler;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        try {
            // Get the session id
            $sessionId = $this->getRequest()->getParam('cko-session-id', null);
            if ($sessionId) {
                // Get the payment details
                $response = $this->apiHandler->getPaymentDetails($sessionId);

                // Set the method ID
                $this->methodId = $response->metadata['methodId'];

                // Logging
                $this->logger->display($response);
                
                // Process the response
                if ($this->apiHandler->isValidResponse($response)) {
                    if (!$this->placeOrder($response)) {
                        // Add and error message
                        $this->messageManager->addErrorMessage(
                            __('The transaction could not be processed or has been cancelled.')
                        );

                        // Return to the cart
                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
                    
                    return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Handles the order placing process.
     *
     * @param array $response The response
     *
     * @return mixed
     */
    protected function placeOrder($response = null)
    {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->methodId)
                ->handleOrder($response, $reservedIncrementId);

            // Add the payment info to the order
            $order = $this->utilities
                ->setPaymentData($order, $response);

            // Save the order
            $order->save();

            // Check if the order is valid
            if (!$this->orderHandler->isOrder($order)) {
                $this->cancelPayment($response);
                return null;
            }

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Cancels a payment.
     *
     * @param array $response The response
     *
     * @return void
     */
    protected function cancelPayment($response)
    {
        try {
            // refund or void accordingly
            if ($this->config->needsAutoCapture($this->methodId)) {
                //refund
                $this->apiHandler->checkoutApi->payments()->refund(new Refund($response->getId()));
            } else {
                //void
                $this->apiHandler->checkoutApi->payments()->void(new Voids($response->getId()));
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }
}
