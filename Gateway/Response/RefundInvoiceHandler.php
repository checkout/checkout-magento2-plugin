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
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Payment;

class RefundInvoiceHandler implements HandlerInterface {

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * RefundInvoiceHandler constructor.
     * @param CreditmemoFactory $creditMemoFactory
     * @param CreditmemoService $creditMemoService
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     */
    public function __construct(
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
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

        if($order->canCreditmemo()) {
            $invoice    = $this->getInvoiceByTransactionId($response['originalId'], $order);
            $amount     = ChargeAmountAdapter::getStoreAmountOfCurrency($response['value'], $response['currency']);

            $creditMemo = $this->creditMemoFactory->createByInvoice($invoice);
            $creditMemo->setBaseGrandTotal($amount);
            $creditMemo->setInvoice($invoice);

            $payment->setCreditmemo($creditMemo);

            $this->orderPaymentRepository->save($payment);
            $this->creditMemoService->refund($creditMemo);
            $this->invoiceRepository->save($invoice);
        }
    }

    /**
     * @param $transactionId
     * @param OrderInterface $order
     * @return InvoiceInterface
     * @throws NoSuchEntityException
     */
    protected function getInvoiceByTransactionId($transactionId, OrderInterface $order) {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaBuilder->addFilter(
            InvoiceInterface::TRANSACTION_ID,
            $transactionId
        );

        $searchCriteriaBuilder->addFilter(
            InvoiceInterface::ORDER_ID,
            $order->getId()
        );

        $searchCriteria = $searchCriteriaBuilder
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $invoiceList = $this->invoiceRepository->getList($searchCriteria);

        if (count($items = $invoiceList->getItems())) {
            /* @var $invoice InvoiceInterface */
            $invoice = current($items);
            $invoice->setOrder($order);
            return $invoice;
        }

        throw new NoSuchEntityException();
    }

}
