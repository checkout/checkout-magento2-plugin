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
 * Class Fail
 */
class Fail extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CheckoutApi
     */
    public $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * PlaceOrder constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
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

            // Restore the quote
            $this->quoteHandler->restoreQuote($response->reference);

            // Logging
            $this->logger->display($response);
        }

        // Display the message
        $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));

        // Return to the cart
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
