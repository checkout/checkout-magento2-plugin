<?php

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenFormatter implements \Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface
{
    /**
     * @var VaultHandlerService
     */
    protected $vaultHandler;

    /**
     * TokenFormatter constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        // Return the formatted token
        return $this->vaultHandler->renderTokenData($paymentToken);
    }
}