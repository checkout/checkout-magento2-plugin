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
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use CheckoutCom\Magento2\Model\GatewayResponseTrait;
use CheckoutCom\Magento2\Model\Method\CallbackMethod;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\StoreCardService;
use CheckoutCom\Magento2\Model\Service\OrderService;

class CallbackService {

    use GatewayResponseTrait;

    /**
     * @var CallbackMethod
     */
    protected $callbackMethod;

    /**
     * @var CallbackEventAdapter
     */
    protected $eventAdapter;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

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
     * @param CallbackMethod $callbackMethod
     * @param CallbackEventAdapter $eventAdapter
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CallbackMethod $callbackMethod,
        CallbackEventAdapter $eventAdapter,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        GatewayConfig $gatewayConfig,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        StoreCardService $storeCardService,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        OrderSender $orderSender,
        OrderService $orderService
    ) {
        $this->callbackMethod   = $callbackMethod;
        $this->eventAdapter     = $eventAdapter;
        $this->orderFactory     = $orderFactory;
        $this->orderRepository  = $orderRepository;
        $this->gatewayConfig    = $gatewayConfig;
        $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;
        $this->storeCardService    = $storeCardService;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->orderSender          = $orderSender;
        $this->orderService          = $orderService;
    }

    /**
     * Runs the service.
     *
     * @throws DomainException
     * @throws LocalizedException
     */
    public function run() {
        if(!$this->gatewayResponse) {
            throw new DomainException('The response is empty.');
        }

        // Set the gateway response
        $this->callbackMethod->setGatewayResponse($this->gatewayResponse);

        // Perform tasks and prepare data
        $commandName    = $this->getCommandName();
        $amount         = $this->getAmount();
        $order      = $this->getAssociatedOrder();
        $payment    = $order->getPayment();

        // Get override comments setting from config
        $overrideComments = $this->gatewayConfig->overrideOrderComments();

        // Process the payment
        if ($payment instanceof Payment) {
            // Prepare payment info
            $infoInstance = $payment->getMethodInstance()->getInfoInstance();
            $this->callbackMethod->setInfoInstance($infoInstance);
            $this->putGatewayResponseToHolder();

            // Test the command name
            if ($commandName == 'refund' || $commandName == 'void') {
                $this->orderService->cancelTransactionFromRemote($order);
            }
            
            // Perform authorize complementary actions
            else if ($commandName == 'authorize') {
                // Update order status
                $order->setStatus($this->gatewayConfig->getOrderStatusAuthorized());

                // Send the email
                $this->orderSender->send($order);

                // Set email sent
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
                $order->setStatus($this->gatewayConfig->getOrderStatusCaptured());

                // Create the invoice
                if ($order->canInvoice() && ($this->gatewayConfig->getAutoGenerateInvoice())) {
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

                // Comments override
                if ($overrideComments) {
                    // Create new comment
                    $newComment = 'Captured amount of ' . ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']) . ' ' . $this->gatewayResponse['response']['message']['currency'] .' Transaction ID: ' . $this->gatewayResponse['response']['message']['id'];

                    // Add the new comment
                    $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);
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

        if($order->isEmpty()) {
            throw new DomainException('The order does not exists.');
        }

        return $order;
    }

    /**
     * Returns the command name.
     *
     * @return null|string
     */
    private function getCommandName() {
        return $this->eventAdapter->getTargetCommandName($this->gatewayResponse['response']['eventType']);
    }

    /**
     * Returns the amount for the store.
     *
     * @return float
     */
    private function getAmount() {
        return ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']);
    }

    /**
     * Sets the gateway response to the holder.
     *
     * @return void
     */
    private function putGatewayResponseToHolder() {
        /* @var $gatewayResponseHolder GatewayResponseHolder */
        $gatewayResponseHolder = ObjectManager::getInstance()->get(GatewayResponseHolder::class);
        $gatewayResponseHolder->setGatewayResponse($this->gatewayResponse['response']['message']);
    }

}
