<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Checkout\Model\Session as CheckoutSession;

class FlowSessionCurrencyGuard
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * Currency recorded for the given Flow session, or null when the session is unknown
     * (e.g. created before this tracking existed, or already evicted from the tracked list).
     */
    public function getSessionCurrency(?string $sessionId): ?string
    {
        if (!$sessionId) {
            return null;
        }

        $sessions = $this->checkoutSession->getFlowSessionCurrencies() ?? [];

        return $sessions[$sessionId] ?? null;
    }

    /**
     * True when the session's recorded currency is known and differs from $currency. An unknown
     * session is treated as a match so a missing record never blocks a legitimate order.
     */
    public function hasCurrencyChanged(?string $sessionId, string $currency): bool
    {
        $recorded = $this->getSessionCurrency($sessionId);

        return $recorded !== null && $recorded !== $currency;
    }
}
