<?php

namespace CheckoutCom\Magento2\Gateway\Request;

use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;

class AmountRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO      = $this->subjectReader->readPayment($buildSubject);
        $order          = $paymentDO->getOrder();

        $currencyCode   = $order->getCurrencyCode();
        $amount         = $this->subjectReader->readAmount($buildSubject);
        $value          = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

        return compact('value');
    }

}
