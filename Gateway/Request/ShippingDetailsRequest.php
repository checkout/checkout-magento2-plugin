<?php

namespace CheckoutCom\Magento2\Gateway\Request;

class ShippingDetailsRequest extends AbstractRequest {

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
        $address        = $order->getShippingAddress();

        if($address === null) {
            return [];
        }

        return [
            'shippingDetails' => [
                'addressLine1'  => $address->getStreetLine1(),
                'addressLine2'  => $address->getStreetLine2(),
                'postcode'      => $address->getPostcode(),
                'country'       => $address->getCountryId(),
                'state'         => $address->getRegionCode(),
                'city'          => $address->getCity(),
                'phone'         => [
                    'number' => $address->getTelephone(),
                ],
            ],
        ];
    }

}
