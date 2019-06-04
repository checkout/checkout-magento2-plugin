<?php

/**
 * Checkout.com
 * Authorised and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

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