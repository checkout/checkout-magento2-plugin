<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

use DomainException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use CheckoutCom\Magento2\Model\Adapter\CallbackEventAdapter;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\StoreCardService;
use CheckoutCom\Magento2\Model\Service\OrderService;

class WebhookCallbackService {

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * CallbackService constructor.
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        Config $gatewayConfig,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        StoreCardService $storeCardService,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        OrderSender $orderSender,
        OrderService $orderService
    ) {
        $this->orderFactory      = $orderFactory;
        $this->orderRepository   = $orderRepository;
        $this->config            = $config;
        $this->invoiceService    = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->storeCardService  = $storeCardService;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->orderSender       = $orderSender;
        $this->orderService      = $orderService;
    }

    /**
     * Runs the service.
     *
     * @throws DomainException
     * @throws LocalizedException
     */
    public function run($response) {
        // Set the gateway response
        $this->gatewayResponse = $response;

        // Extract the response info
        $commandName    = $this->getCommandName();
        $amount         = $this->getAmount();

        // Get the order qnd pqyment information
        $order          = $this->getAssociatedOrder();
        $payment        = $order->getPayment();

        // Get override comments setting from config
        $overrideComments = $this->config->overrideOrderComments();

        // Process the payment
        if ($payment instanceof Payment) {
            // Test the command name
            if ($commandName == 'refund' || $commandName == 'void') {
                $this->orderService->cancelTransactionFromRemote($order);
            }
            
            // Perform authorize complementary actions
            else if ($commandName == 'authorize') {
                // Update order status
                $order->setStatus($this->config->getOrderStatusAuthorized());

                // Send the email
                $this->orderSender->send($order);
                $order->setEmailSent(1);

                // Comments override
                if ($overrideComments) {
                    // Delete comments history
                    foreach ($order->getAllStatusHistory() as $orderComment) {
                        $orderComment->delete();
                    } 

                    // Create new comment
                    $newComment = 'Authorized amount of ' . ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']) . ' ' . $this->gatewayResponse['response']['message']['currency'] .' Transaction ID: ' . $this->gatewayResponse['response']['message']['id'];

                    // Add the new comment
                    $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);
                }
            }

            // Perform capture complementary actions
            else if ($commandName == 'capture') {
                // Update order status
                $order->setStatus($this->config->getOrderStatusCaptured());

                // Create the invoice
                if ($order->canInvoice() && ($this->config->getAutoGenerateInvoice())) {
                    $amount = ChargeAmountAdapter::getStoreAmountOfCurrency(
                        $this->gatewayResponse['response']['message']['value'],
                        $this->gatewayResponse['response']['message']['currency']
                    );
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                    $invoice->setBaseGrandTotal($amount);
                    $invoice->register();

                    $this->invoiceRepository->save($invoice);
                }
            }

            // Save the order
            $this->orderRepository->save($order);
        }
    }

    /**
     * Returns the order instance.
     *
     * @return \Magento\Sales\Model\Order
     * @throws DomainException
     */
    private function getAssociatedOrder() {
        $orderId    = $this->gatewayResponse['response']['message']['trackId'];
        $order      = $this->orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->isEmpty()) return $order

        return null;
    }

    /**
     * Returns the command name.
     *
     * @return null|string
     */
    private function getCommandName() {
        return $this->gatewayResponse['response']['eventType'];
    }

    /**
     * Returns the amount for the store.
     *
     * @return float
     */
    private function getAmount() {
        return ChargeAmountAdapter::getStoreAmountOfCurrency(
            $this->gatewayResponse['response']['message']['value'],
            $this->gatewayResponse['response']['message']['currency']
        );
    }
}