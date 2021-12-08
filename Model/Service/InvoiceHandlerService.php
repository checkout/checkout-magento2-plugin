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

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class InvoiceHandlerService.
 */
class InvoiceHandlerService
{
    /**
     * $invoiceService field
     *
     * @var InvoiceService $invoiceService
     */
    public $invoiceService;
    /**
     * $invoiceRepository field
     *
     * @var InvoiceRepositoryInterface $invoiceRepository
     */
    public $invoiceRepository;
    /**
     * $invoiceModel field
     *
     * @var Invoice $invoiceModel
     */
    public $invoiceModel;
    /**
     * $order field
     *
     * @var Order $order
     */
    public $order;
    /**
     * $transaction field
     *
     * @var Transaction $transaction
     */
    public $transaction;
    /**
     * $amount field
     *
     * @var Float $amount
     */
    public $amount;

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
     * @param $transaction
     * @param $amount
     *
     * @return void
     * @throws LocalizedException
     */
    public function createInvoice($transaction, $amount)
    {
        // Set required properties
        $this->order       = $transaction->getOrder();
        $this->transaction = $transaction;
        $this->amount      = $amount;

        // Prepare the invoice
        $invoice = $this->invoiceService->prepareInvoice($this->order);

        // Set the invoice transaction ID
        if ($this->transaction) {
            $invoice->setTransactionId($this->transaction->getTxnId());
        }

        // Set the invoice state
        $invoice = $this->setInvoiceState($invoice);

        // Finalize the invoice
        $invoice->setBaseGrandTotal($amount / $this->order->getBaseToOrderRate());
        $invoice->setGrandTotal($this->amount);
        $invoice->register();

        // Save the invoice
        $this->invoiceRepository->save($invoice);
    }

    /**
     * Check if invoicing is needed
     *
     * @return bool
     */
    public function needsInvoicing()
    {
        return $this->transaction && $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
    }

    /**
     * Set the invoice state
     *
     * @param $invoice
     *
     * @return mixed
     */
    public function setInvoiceState($invoice)
    {
        if ($this->needsInvoicing()) {
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setState(Invoice::STATE_PAID);
            $invoice->setCanVoidFlag(false);
        }

        return $invoice;
    }

    /**
     * Load an order invoice
     *
     * @param $order
     *
     * @return Invoice|null
     */
    public function getInvoice($order)
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
     * @param $order
     *
     * @return mixed
     */
    public function getInvoices($order)
    {
        return $order->getInvoiceCollection();
    }
}
