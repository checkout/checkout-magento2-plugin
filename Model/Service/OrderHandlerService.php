<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;

class OrderHandlerService
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchBuilder;
     
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionHandler;
     
    /**
     * @var String
     */
    protected $methodId;

    /**
     * @var Array
     */
    protected $paymentData;

    /**
     * OrderHandler constructor
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchBuilder,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderInterface  = $orderInterface;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
        $this->quoteHandler = $quoteHandler;
        $this->transactionHandler = $transactionHandler;
    }

    /**
     * Set the payment method id
     */
    public function setMethodId($methodId) {
        $this->methodId = $methodId;
        return $this;
    }

    /**
     * Set payment data for transactions
     */
    public function setPaymentData($paymentData) {
        if ($paymentData) {
            $this->paymentData = (array) $paymentData;
        }

        return $this;
    }

    /**
     * Places an order if not already created
     */
    public function placeOrder($reservedIncrementId = '')
    {
        if ($this->methodId) {
            try {
                //  Prepare a fields filter
                $filters = ['reserved_order_id' => $reservedIncrementId];

                // Check if the order exists
                $order = $this->getOrder($filters);

                // Create the order
                if (!$this->isOrder($order)) {
                    // Prepare the quote
                    $quote = $this->prepareQuote($filters);
                    if ($quote) {
                        // Create the order
                        $order = $this->quoteManagement->submit($quote);

                        // Process the transactions for the order
                        $this->processTransactions($quote, $order);
                    }
                }

                // Return the saved order
                $order = $this->orderRepository->save($order);

                // Perform after place order tasks
                $order = $this->afterPlaceOrder($this->quote, $order);

                return $order;

            } catch (\Exception $e) {

                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/placeorder.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($e->getMessage());

                return false;
            }
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('A payment method ID is required to place an order.')
            );
        }
    }

    /**
     * Checks if an order exists and is valid
     */
    public function isOrder($order)
    {
        return $order
        && is_object($order)
        && method_exists($order, 'getId')
        && $order->getId() > 0;
    }

    /**
     * Load an order
     */
    public function getOrder($fields = [])
    {
        try {
            if ($fields && is_array($fields) && count($fields) > 0) {
                return $this->findOrderByFields($fields);
            }
            else {
                // Try to find and order id in session
                return $this->orderRepository->get(
                    $this->checkoutSession->getLastOrderId()
                );
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find an order by fields
     */
    public function findOrderByFields($fields) {
        try {
            // Add each field as filter
            foreach ($fields as $key => $value) {
                $this->searchBuilder->addFilter(
                    $key,
                    $value
                );
            }
            
            // Create the search instance
            $search = $this->searchBuilder->create();

            // Get the resultin order
            $order = $this->orderRepository
                ->getList($search)
                ->getFirstItem();

            return $order;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Prepares a quote for order placement
     */
    public function prepareQuote($fields = [])
    {
        // Find quote and perform tasks
        $quote = $this->quoteHandler->getQuote($fields);
        if ($this->quoteHandler->isQuote($quote)) {
            // Prepare the inventory
            $quote->setInventoryProcessed(false);

            // Check for guest user quote
            if ($this->customerSession->isLoggedIn() === false) {
                $quote = $this->quoteHandler->prepareGuestQuote($quote);
            }

            // Set the payment information
            $payment = $quote->getPayment();
            $payment->setMethod($this->methodId);
            $payment->save();

            return $quote;
        }

        return null;
    }

    /**
     * Handle the order transactions
     */
    public function processTransactions($quote, $order)
    {
        // Handle the transactions 
        if ($this->config->isAutoCapture($this->methodId)) {
            // Create the transaction
            $transactionId = $this->transactionHandler->createTransaction
            (
                $order,
                Transaction::TYPE_CAPTURE,
                $this->paymentData
            );
        } else {
            // Update order status
            // Todo - Add order status handling settings
            /*
            $order->setStatus(
                $this->config->params[Core::moduleId()][Connector::KEY_ORDER_STATUS_AUTHORIZED]
            );
            */

            // Create the transaction
            $transactionId = $this->transactionHandler->createTransaction
            (
                $order,
                Transaction::TYPE_AUTH,
                $this->paymentData
            );
        }
    }  

    /**
     * Tasks after place order
     */
    public function afterPlaceOrder($quote = null, $order = null)
    {
        try {
            // Find the quote and the order
            $quote = $quote ? $quote : $this->quoteHandler->getQuote();
            $order = $order ? $order : $this->getOrder();

            // Prepare session quote info for redirection after payment
            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();

            // Prepare session order info for redirection after payment
            $this->checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            return $order;
        } catch (\Exception $e) {
            return false;
        }
    }
}