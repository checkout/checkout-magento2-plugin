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

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use CheckoutCom\Magento2\Model\Adapter\CcTypeAdapter;

class CardDetailsHandler implements HandlerInterface {

    /**
     * @var CcTypeAdapter
     */
    protected $ccTypeAdapter;

    /**
     * CardDetailsHandler constructor.
     * @param CcTypeAdapter $ccTypeAdapter
     */
    public function __construct(CcTypeAdapter $ccTypeAdapter) {
        $this->ccTypeAdapter = $ccTypeAdapter;
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
        
        if( ! array_key_exists('card', $response) ) {
            return;
        }
        
        /** @var $payment Payment */
        $paymentDO      = SubjectReader::readPayment($handlingSubject);
        $payment        = $paymentDO->getPayment();
        $cardDetails    = $response['card'];

        if( ! $payment instanceof Payment) {
            return;
        }

        $cardType = $this->ccTypeAdapter->getFromGateway($cardDetails['paymentMethod']);

        $payment->setCcLast4($cardDetails['last4']);
        $payment->setCcExpMonth($cardDetails['expiryMonth']);
        $payment->setCcExpYear($cardDetails['expiryYear']);
        $payment->setCcType($cardType);

        $payment->setAdditionalInformation('card_number', 'xxxx-' . $cardDetails['last4']);
        $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $cardType);
    }

}
