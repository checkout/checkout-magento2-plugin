<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Invoice;

class InvoiceHandlerService
{
    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Order
     */
    protected $order;

    /**
     * InvoiceHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
    	\CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->config             = $config;
    }

    /**
     * Set the order instance.
     */
    public function setOrder($order) {
        $this->order = $order;
    }

    /**
     * Check if the invoice can be created.
     */
    public function processInvoice()
    {
        if ($this->order->canInvoice()) {
            $this->createInvoice();
        }
    }

    /**
     * Create the invoice.
     */
    public function createInvoice()
    {
        try {
            // Prepare the invoice
            $invoice = $this->invoiceService->prepareInvoice($this->order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();

            // Save the invoice
            $this->invoiceRepository->save($invoice);
        } catch (\Exception $e) {

        }
    }
}
