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
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

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
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
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
            // Get the store code
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            // Get the payment details
            $response = $api->getPaymentDetails($sessionId);

            // Logging
            $this->logger->display($response);
        }

        // Don't restore quote if saved card request
        if ($response->amount !== 0 && $response->amount !== 100) {
            // Restore the quote
            $this->quoteHandler->restoreQuote($response->reference);

            // Display the message
            $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));

            // Return to the cart
            return $this->_redirect('checkout/cart', ['_secure' => true]);

        } else {

            $this->messageManager->addErrorMessage(
                __('The card could not be saved.')
            );

            // Return to the cart
            return $this->_redirect('vault/cards/listaction', ['_secure' => true]);
        }
    }
}