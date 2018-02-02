<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Request;

use Magento\Sales\Model\Order\Payment;
use InvalidArgumentException;

class ParentChargeIdRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO  = $this->subjectReader->readPayment($buildSubject);
        $payment    = $paymentDO->getPayment();

        if($payment instanceof Payment) {
            $chargeId = $payment->getParentTransactionId();

            if($chargeId === null) {
                throw new InvalidArgumentException('Parent transaction ID is empty.');
            }

            return compact('chargeId');
        }

        return [];
    }

}
