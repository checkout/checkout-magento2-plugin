<?php

namespace Cmsbox\Mercanet\Model\Service;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

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

    public function processInvoice($order)
    {
        // Assign the order
        $this->order = $order;

        // Check invoicing available
        if ($this->shouldInvoice()) {
            $this->createInvoice();
        }
    }

    public function shouldInvoice()
    {
        try {
    
            // Get the method id
            $methodId = $this->order->getPayment()
                ->getMethodInstance()
                ->getCode();

            // Get the auto generate invoice setting
            $autoGenerateInvoice = $this->config->getValue(
                'auto_generate_invoice',
                $methodId
            );    

            // Return the test
            return ($this->order->canInvoice() && $autoGenerateInvoice);
        } catch (\Exception $e) {
            return false;
        }
    }

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
