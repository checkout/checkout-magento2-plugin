<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class CanVoidHandler implements ValueHandlerInterface {


    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null) {
        
        $paymentDO  = SubjectReader::readPayment($subject);
        $payment    = $paymentDO->getPayment();
        
        if(!$payment instanceof Payment OR ! (bool)$payment->getAmountPaid()){
            return false;
        }
        
        $authTransaction = $payment->getAuthorizationTransaction();
        
        return (bool)$authTransaction AND !$authTransaction->getIsClosed();
    }

}
