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
        $amount         = ChargeAmountAdapter::getPaymentFinalCurrencyValue($this->subjectReader->readAmount($buildSubject));

        $currencyCode   = ChargeAmountAdapter::getPaymentFinalCurrencyCode($order->getCurrencyCode());
        $value          = ChargeAmountAdapter::getGatewayAmountOfCurrency($amount, $currencyCode);

        return compact('value');
    }

}
