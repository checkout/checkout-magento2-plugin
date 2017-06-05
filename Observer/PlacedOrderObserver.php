<?php

namespace CheckoutCom\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;

class PlacedOrderObserver implements ObserverInterface {

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var Session 
     */
    protected $customerSession;

    /**
     * PlacedOrderObserver constructor.
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param GatewayConfig $gatewayConfig
     * @param CustomerSession $customerSession
     */
    public function __construct(InvoiceService $invoiceService, InvoiceRepositoryInterface $invoiceRepository, GatewayConfig $gatewayConfig, Order $orderModel, CustomerSession $customerSession) {
        $this->gatewayConfig        = $gatewayConfig;
        $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;
        $this->orderModel = $orderModel;
        $this->customerSession      = $customerSession;
    }

    /**
     * Handles the observer for placed orders.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
   
        // Get Order id
        $orderId = $observer->getData('order_ids');   

        // Load the order
        $order = $this->orderModel->load($orderId);

        // Update order status
        $order->setState('new');
        $order->setStatus($this->gatewayConfig->getNewOrderStatus());

        // Set email sent
        $order->setEmailSent(1);

        // Save the order
        $order->save(); 

        // Get the response data set in TransactionHandler
        $response = $this->customerSession->getResponseData();
        if (isset($response) && is_array($response)) {

            // Delete comments history
            foreach ($order->getAllStatusHistory() as $orderComment) {
                $orderComment->delete();
            } 

            // Add new comment
            $newComment = 'Authorized amount of ' . ChargeAmountAdapter::getStoreAmountOfCurrency($response['value'], $response['currency']) . ' ' . $response['currency'] .' Transaction ID: ' . $response['id'];
            $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);

            // Save the order
            $order->save();

            // Delete the charge id from session
            $this->customerSession->unsResponseData();
        }

        // Generate invoice if needed
        if($this->gatewayConfig->getAutoGenerateInvoice() && $order->canInvoice()) {

            // Prepare the invoice
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            // Save the invoice
            $this->invoiceRepository->save($invoice);
        }    
    }

}
