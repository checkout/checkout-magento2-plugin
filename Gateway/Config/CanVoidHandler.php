<?php

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
