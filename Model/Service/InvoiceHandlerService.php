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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class InvoiceHandlerService
 */
class InvoiceHandlerService
{
    /**
     * $invoiceService field
     *
     * @var InvoiceService $invoiceService
     */
    private $invoiceService;
    /**
     * $invoiceRepository field
     *
     * @var InvoiceRepositoryInterface $invoiceRepository
     */
    private $invoiceRepository;
    /**
     * $invoiceModel field
     *
     * @var Invoice $invoiceModel
     */
    private $invoiceModel;

    /**
     * InvoiceHandlerService constructor
     *
     * @param InvoiceService             $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Invoice                    $invoiceModel
     */
    public function __construct(
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        Invoice $invoiceModel
    ) {
        $this->invoiceService    = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceModel      = $invoiceModel;
    }

    /**
     * Create an invoice
     *
     * @param TransactionInterface $transaction
     * @param float                $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function createInvoice(TransactionInterface $transaction, float $amount): void
    {
        /** @var Order $order */
        $order = $transaction->getOrder();

        // Prepare the invoice
        /** @var Invoice $invoice */
        $invoice = $this->invoiceService->prepareInvoice($order);

        // Set the invoice transaction ID
        $invoice->setTransactionId($transaction->getTxnId());

        // Set the invoice state
        $invoice = $this->setInvoiceState($invoice, $transaction);

        // Finalize the invoice
        $invoice->setBaseGrandTotal($amount / $order->getBaseToOrderRate());
        $invoice->setGrandTotal($amount);
        $invoice->register();

        // Save the invoice
        $this->invoiceRepository->save($invoice);
    }

    /**
     * Check if invoicing is needed
     *
     * @param TransactionInterface $transaction
     *
     * @return bool
     */
    public function needsInvoicing(TransactionInterface $transaction): bool
    {
        return $transaction->getTxnType() === TransactionInterface::TYPE_CAPTURE;
    }

    /**
     * Set the invoice state
     *
     * @param InvoiceInterface     $invoice
     * @param TransactionInterface $transaction
     *
     * @return InvoiceInterface
     */
    public function setInvoiceState(InvoiceInterface $invoice, TransactionInterface $transaction): InvoiceInterface
    {
        if ($this->needsInvoicing($transaction)) {
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setState(Invoice::STATE_PAID);
            $invoice->setCanVoidFlag(false);
        }

        return $invoice;
    }

    /**
     * Load an order invoice
     *
     * @param Order $order
     *
     * @return Invoice|null
     */
    public function getInvoice(Order $order): ?Invoice
    {
        // Get the invoices collection
        $invoices = $order->getInvoiceCollection();

        // Retrieve the invoice increment id
        if (!empty($invoices)) {
            foreach ($invoices as $item) {
                $invoiceIncrementId = $item->getIncrementId();
            }

            // Load an invoice
            return $this->invoiceModel->loadByIncrementId($invoiceIncrementId);
        }

        return null;
    }

    /**
     * Load all order invoices
     *
     * @param Order $order
     *
     * @return InvoiceCollection
     */
    public function getInvoices(Order $order): InvoiceCollection
    {
        return $order->getInvoiceCollection();
    }
}
