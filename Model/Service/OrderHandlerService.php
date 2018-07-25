<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartManagementInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use CheckoutCom\Magento2\Model\Service\InvoiceHandlerService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class OrderHandlerService {

    /**
     * @var TransactionHandlerService
     */
    protected $transactionService;

    /**
     * @var InvoiceHandlerService
     */
    protected $invoiceHandlerService;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Array
     */
    protected $data;

    /**
     * CallbackService constructor.
     * @param TransactionHandlerService $transactionService
     * @param InvoiceHandlerService $invoiceHandlerService
     * @param CartManagementInterface $cartManagement
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param OrderSender $orderSender
     * @param Tools $tools
     * @param Watchdog $watchdog
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $cartRepository
     * @param OrderInterface $orderInterface
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        TransactionHandlerService $transactionService,
        InvoiceHandlerService $invoiceHandlerService,
        CartManagementInterface $cartManagement,
        CheckoutSession $checkoutSession,
        Config $config,
        CustomerSession $customerSession,
        OrderSender $orderSender,
        Tools $tools,
        Watchdog $watchdog,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,                   
        OrderInterface $orderInterface,
        QuoteFactory $quoteFactory
    ) {
        $this->transactionService    = $transactionService;
        $this->invoiceHandlerService = $invoiceHandlerService;
        $this->cartManagement        = $cartManagement;
        $this->checkoutSession       = $checkoutSession;
        $this->customerSession       = $customerSession;
        $this->config                = $config;
        $this->orderSender           = $orderSender;
        $this->tools                 = $tools;
        $this->watchdog              = $watchdog;
        $this->orderRepository       = $orderRepository;
        $this->cartRepository        = $cartRepository;
        $this->orderInterface        = $orderInterface;
        $this->quoteFactory          = $quoteFactory;
    }

    public function placeOrder($data) {
        // Assign the gateway response
        $this->data = json_decode($data);

        // Check if the order exists
        if (isset($this->data->trackId)) {
            // Load an order from increment id
            $order = $this->orderInterface->loadByIncrementId($this->data->trackId);

            // If the order exists update it, else create it
            if ($order->getId() > 0) {
                return $this->updateExistingOrder($order);
            }
            else {
                return $this->createNewOrder();
            }
        }

        return false;
    }

    public function createNewOrder($quote = null) { 
        // Get the quote
        $quote = ($quote) ? $quote : $this->checkoutSession->getQuote();

        // Process the quote
        if ($this->tools->quoteIsValid($quote)) {
            // Prepare the quote payment
            $quote->setPaymentMethod(ConfigProvider::CODE);
            $quote->getPayment()->importData (array('method' => ConfigProvider::CODE));

            // Prepare the inventory
            $quote->setInventoryProcessed(false);

            // Check for guest user quote
            if ($quote->getCustomerEmail() === null && $this->customerSession->isLoggedIn() === false)
            {
                $quote->setCustomerId(null)
                ->setCustomerEmail($this->data['email'])
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }

            // Save the quote
            $quote->collectTotals ();
            $quote->save();

            // Create the order
            $orderId = $this->cartManagement->placeOrder($quote->getId());
        
            // Return the order id
            return (int) $orderId;
        }
    }

    public function updateExistingOrder($order) { 

    }
 
}