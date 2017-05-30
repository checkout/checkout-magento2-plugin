<?php

namespace CheckoutCom\Magento2\Gateway\Request;

class CardTokenRequest extends AbstractRequest {

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        return [
            'cardToken' => $paymentDO->getPayment()->getAdditionalInformation('card_token_id'),
        ];
    }

}
