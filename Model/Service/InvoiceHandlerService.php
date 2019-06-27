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

/**
 * Class InvoiceHandlerService.
 */
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
            // Set required properties
            $this->order = $order;
            $this->transaction = $transaction;

            // Handle the invoice
            if ($this->needsInvoicing()) {
                $this->createInvoice();
            } elseif ($this->needsCancelling()) {
                $this->cancelInvoice();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create an invoice.
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

            // Set the invoice state
            $invoice = $this->setInvoiceState($invoice);

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
     * Cancel an invoice for void or refund.
     */
    public function cancelInvoice()
    {
        $invoice = $this->getInvoice($this->order);
        if ($invoice) {
            $invoice->setState($this->invoiceModel::STATE_CANCELED);
            $this->invoiceRepository->save($invoice);
        }
    }

    /**
     * Check if invoicing is needed.
     */
    public function needsInvoicing()
    {
        return $this->needsCaptureInvoice() || $this->needsAuthorizationInvoice();
    }

    /**
     * Check invoice cancelling is needed.
     */
    public function needsCancelling()
    {
        return $this->transaction->getTxnType() == Transaction::TYPE_VOID
        || $this->transaction->getTxnType() == Transaction::TYPE_REFUND;
    }

    /**
     * Check if a transaction is capture type.
     */
    public function needsCaptureInvoice()
    {
        return $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE
        && $this->config->getValue('invoice_generation') == 'authorize_capture';
    }

    /**
     * Check if a transaction is authorization type.
     */
    public function needsAuthorizationInvoice()
    {
        return $this->transaction->getTxnType() == Transaction::TYPE_AUTH
        && $this->config->getValue('invoice_generation') == 'authorize';
    }

    /**
     * Set the invoice state.
     */
    public function setInvoiceState($invoice)
    {
        try {
            if ($this->needsCaptureInvoice()) {
                $invoice->setRequestedCaptureCase($this->invoiceModel::CAPTURE_ONLINE);
                //$invoice->setState($this->invoiceModel::STATE_PAID);
                $invoice->setCanVoidFlag(false);
            } elseif ($this->needsAuthorizationInvoice()) {
                $invoice->setState($this->invoiceModel::STATE_OPEN);
                $invoice->setRequestedCaptureCase($this->invoiceModel::NOT_CAPTURE);
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $invoice;
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
            if (count($invoices) > 0) {
                foreach ($invoices as $item) {
                    $invoiceIncrementId = $item->getIncrementId();
                }

                // Load an invoice
                return $this->invoiceModel->loadByIncrementId($invoiceIncrementId);
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
