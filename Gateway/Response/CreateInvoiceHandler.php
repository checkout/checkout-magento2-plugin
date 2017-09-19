<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Response;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

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
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * CreateInvoiceHandler constructor.
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(InvoiceService $invoiceService, InvoiceRepositoryInterface $invoiceRepository, GatewayConfig $gatewayConfig) {
        $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;
        $this->gatewayConfig        = $gatewayConfig;
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

        if($order->canInvoice() && ($this->gatewayConfig->getAutoGenerateInvoice())) {
            $amount = ChargeAmountAdapter::getStoreAmountOfCurrency($response['value'], $response['currency']);

            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setBaseGrandTotal($amount);
            $invoice->register();

            $this->invoiceRepository->save($invoice);
        }
    }

}
