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
