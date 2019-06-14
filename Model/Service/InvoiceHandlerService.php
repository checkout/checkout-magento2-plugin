<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
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
     * @var Invoice
     */
    protected $invoiceModel;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * InvoiceHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Invoice $invoiceModel,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->invoiceModel       = $invoiceModel;
        $this->config             = $config;
        $this->logger             = $logger;
    }

    /**
     * Check if the invoice can be created.
     */
    public function processInvoice($order, $transaction = null)
    {
        try {
            $this->order = $order;
            $this->transaction = $transaction;
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

            // Set the invoice transaction ID
            if ($this->transaction) {
                $invoice->setTransactionId($this->transaction->getTxnId());
            }

            // Set the invoice status
            if ($this->transaction && $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE) {
                $invoice->setState($this->invoiceModel::STATE_PAID);
                $invoice->setRequestedCaptureCase($this->invoiceModel::CAPTURE_ONLINE);
            }
            else {
                $invoice->setState($this->invoiceModel::STATE_OPEN);
                $invoice->setRequestedCaptureCase($this->invoiceModel::NOT_CAPTURE);                
            }

            // Finalize the invoice
            $invoice->setBaseGrandTotal($this->order->getGrandTotal());
            $invoice->register();

            // Save the invoice
            $this->invoiceRepository->save($invoice);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Load order invoices.
     */
    public function getInvoice($order)
    {
        try {
            // Get the invoices collection
            $invoices = $order->getInvoiceCollection();

            // Retrieve the invoice increment id
            foreach ($invoices as $item) {
                $invoiceIncrementId = $item->getIncrementId();
            }

            // Load an invoice
            return $this->invoiceModel->loadByIncrementId($invoiceIncrementId); 

        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
