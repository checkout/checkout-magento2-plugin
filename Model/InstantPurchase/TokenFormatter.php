<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenFormatter implements \Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface
{
    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        // Get the card details
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);

        // Return the formatted token
        return sprintf(
            '%s: %s, %s: %s (%s: %s)',
            __('Card type'),
            $details['type'],
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );        
    }
}