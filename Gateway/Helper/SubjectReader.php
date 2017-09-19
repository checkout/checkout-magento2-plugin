<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Helper;

use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use InvalidArgumentException;

class SubjectReader {

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject) {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject) {
        return Helper\SubjectReader::readAmount($subject);
    }

    /**
     * Reads customer id from subject
     *
     * @param array $subject
     * @return int
     * @throws InvalidArgumentException
     */
    public function readCustomerId(array $subject) {
        if ( ! array_key_exists('customer_id', $subject) ) {
            throw new InvalidArgumentException('The "customerId" field does not exists');
        }

        return (int) $subject['customer_id'];
    }

}
