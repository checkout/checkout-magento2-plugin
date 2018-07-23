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

use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class InvoiceHandlerService {

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
     * InvoiceHandlerService constructor.
     * @param GatewayConfig $gatewayConfig
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
    */
    public function __construct(
        GatewayConfig $gatewayConfig,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository        
    ) {
        $this->gatewayConfig      = $gatewayConfig;
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
    }

    public function processInvoice($order) {
        if ($this->shouldInvoice($order))  $this->createInvoice($order);
    }

    public function shouldInvoice($order) {
        return $order->canInvoice() 
        && ($this->gatewayConfig->getAutoGenerateInvoice())
        && (
            $this->gatewayConfig->getInvoiceCreationMode() == 'capture' && $this->gatewayConfig->isAutocapture() ||
            $this->gatewayConfig->getInvoiceCreationMode() == 'authorization' && !$this->gatewayConfig->isAutocapture()
        );
    }

    public function createInvoice($order) {
        // Prepare the invoice
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setBaseGrandTotal($order->getGrandTotal());
        $invoice->register();

        // Save the invoice
        $this->invoiceRepository->save($invoice);
    }
}