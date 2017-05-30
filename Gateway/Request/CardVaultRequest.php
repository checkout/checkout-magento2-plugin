<?php

namespace CheckoutCom\Magento2\Gateway\Request;

class CardVaultRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO  = $this->subjectReader->readPayment($buildSubject);
        $payment    = $paymentDO->getPayment();

        return [
            'cardId' => $payment->getExtensionAttributes()->getVaultPaymentToken()->getGatewayToken()
        ];
    }

}
