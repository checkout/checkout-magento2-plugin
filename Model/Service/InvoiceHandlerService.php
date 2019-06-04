<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
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
     * @var Logger
     */
    protected $logger;

    /**
     * InvoiceHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->config             = $config;
        $this->logger             = $logger;
    }

    /**
     * Check if the invoice can be created.
     */
    public function processInvoice($order)
    {
        try {
            $this->order = $order;
            if ($this->order->canInvoice()) {
                return $this->createInvoice();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
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
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
