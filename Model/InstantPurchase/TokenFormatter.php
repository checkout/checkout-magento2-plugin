<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenFormatter implements \Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface
{
    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * TokenFormatter constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->utilities = $utilities;
    }

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
            $this->utilities->getCardName($details['type']),
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );        
    }
}