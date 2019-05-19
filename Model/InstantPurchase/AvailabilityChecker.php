<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

class AvailabilityChecker implements \Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface
{
    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        return true;
    }
}