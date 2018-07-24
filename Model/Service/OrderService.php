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
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\QuoteFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Model\Service\TransactionService;
use CheckoutCom\Magento2\Model\Service\InvoiceHandlerService;

class OrderService {

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var InvoiceHandlerService
     */
    protected $invoiceHandlerService;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

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
     * CallbackService constructor.
     * @param TransactionService $transactionService
     * @param InvoiceHandlerService $invoiceHandlerService
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param JsonFactory $resultJsonFactory
     * @param OrderSender $orderSender
     * @param Tools $tools
     * @param Watchdog $watchdog
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $cartRepository
     * @param OrderInterface $orderInterface
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        TransactionService $transactionService,
        InvoiceHandlerService $invoiceHandlerService,
        CheckoutSession $checkoutSession,
        Config $config,
        CustomerSession $customerSession,
        QuoteManagement $quoteManagement, 
        JsonFactory $resultJsonFactory,
        OrderSender $orderSender,
        Tools $tools,
        Watchdog $watchdog,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,                   
        OrderInterface $orderInterface,
        QuoteFactory $quoteFactory
    ) {
        $this->transactionService = $transactionService;
        $this->invoiceHandlerService = $invoiceHandlerService;
        $this->checkoutSession       = $checkoutSession;
        $this->customerSession       = $customerSession;
        $this->quoteManagement       = $quoteManagement;
        $this->resultJsonFactory     = $resultJsonFactory;
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
        // Get the quote
        $quote = $this->checkoutSession->getQuote();
        if ((int) $quote->getId() > 0) {
            $this->createOrder($quote);
        }
        else
        {
            throw new LocalizedException(__('There was no quote found to create an order.'));            
        }

        return $order;
    }

    private function createOrder($quote) {
        // Create the order
        $order = $this->quoteManagement->submit($quote);

        // Prepare required variables
        $newComment = '';

        // Update order status
        if ($this->config->isAutocapture()) {
            // Update order status
            $order->setStatus($this->config->getOrderStatusCaptured());
            $this->orderRepository->save($order);

            // Create the transaction
            $transactionId = $this->transactionService->createTransaction($order, $fields, 'capture');
            $newComment .= __('Captured') . ' '; 
        }
        else {
            // Update order status
            $order->setStatus($this->config->getOrderStatusAuthorized());

            // Create the transaction
            $transactionId = $this->transactionService->createTransaction($order, $fields, 'authorization');
            $newComment .= __('Authorized') . ' '; 
        }

        // Create the invoice
        $this->invoiceHandlerService->processInvoice($order);   

        // Create new comment
        $amount = $this->checkoutSession->getQuote()->getGrandTotal()*100;
        $newComment .= __('amount of') . ' ' . $amount . ' ' . $order->getOrderCurrencyCode(); 
        $newComment .= ' ' . __('Transaction ID') . ': ' . $transactionId;
        
        // Set the order status
        $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);

        // Save the order
        $this->orderRepository->save($order);

        // Send the email
        $this->orderSender->send($order);
        $order->setEmailSent(1);     

        return $order; 
    }
}