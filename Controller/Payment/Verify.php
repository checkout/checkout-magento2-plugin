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

class Verify extends \Magento\Framework\App\Action\Action
{
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
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->orderHandler = $orderHandler;
        $this->utilities = $utilities;
        $this->logger = $logger;

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();

        // Todo - make the method detection generic for 3ds card payments and APMs
        $this->methodId = 'checkoutcom_card_payment';
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

                // Logging
                $this->logger->display($response);
                
                // Process the response
                if ($this->apiHandler->isValidResponse($response)) {

                    if (!$this->placeOrder($response)) {
                        // Todo - Handle the refund as in placeOrder if order creation fails
                    }

                    return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }

        // Add and error message
        $this->messageManager->addErrorMessage(__('The transaction could not be processed or has been cancelled.'));

        // Return to the cart
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    /**
     * Handles the order placing process.
     *
     * @param array $response The response
     *
     * @return mixed
     */
    protected function placeOrder(array $response = null)
    {
        try {
            // Get the reserved order increment id
            $reservedIncrementId = $this->quoteHandler
                ->getReference($this->quote);

            // Create an order
            $order = $this->orderHandler
                ->setMethodId($this->methodId)
                ->handleOrder($reservedIncrementId, $response);

            // Add the payment info to the order
            $order = $this->utilities
                ->setPaymentData($order, $response);

            return $order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
