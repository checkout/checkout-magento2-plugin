<?php

namespace CheckoutCom\Magento2\Gateway\Response;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Payment;

class CreateInvoiceHandler implements HandlerInterface {

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * CreateInvoiceHandler constructor.
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(InvoiceService $invoiceService, InvoiceRepositoryInterface $invoiceRepository) {
        $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response) {
        
        /** @var $payment Payment */
        $paymentDO  = SubjectReader::readPayment($handlingSubject);
        $payment    = $paymentDO->getPayment();
        $order      = $payment->getOrder();

        if($order->canInvoice()) {
            $amount = ChargeAmountAdapter::getStoreAmountOfCurrency($response['value'], $response['currency']);

            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setBaseGrandTotal($amount);
            $invoice->register();

            $this->invoiceRepository->save($invoice);
        }
    }

}
