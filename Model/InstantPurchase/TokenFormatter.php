<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Class TokenFormatter
 */
class TokenFormatter
{
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;

    /**
     * TokenFormatter constructor
     *
     * @param VaultHandlerService $vaultHandler
     */
    public function __construct(
        VaultHandlerService $vaultHandler
    ) {
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Description formatPaymentToken function
     *
     * @param PaymentTokenInterface $paymentToken
     *
     * @return string
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        // Return the formatted token
        return $this->vaultHandler->renderTokenData($paymentToken);
    }
}
