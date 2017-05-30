<?php

namespace CheckoutCom\Magento2\Gateway\Response;

use Magento\Sales\Api\OrderManagementInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class CancelOrderHandler implements HandlerInterface {

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * CancelOrderHandler constructor.
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(OrderManagementInterface $orderManagement) {
        $this->orderManagement = $orderManagement;
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

        $this->orderManagement->cancel( $order->getId() );
    }

}
