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

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class OnlineCaptureGuard
{
    public function __construct(
        private readonly Session $backendAuthSession,
        private readonly State $appState
    ) {
    }

    /**
     * True when an invoice capture must be sent to Checkout.com: from the admin, or from an
     * integration calling the web API without any admin session. Invoices created by webhooks
     * are already paid when registered, so they never reach the payment method capture.
     */
    public function canCaptureOnline(): bool
    {
        return $this->backendAuthSession->isLoggedIn() || $this->isWebApiRequest();
    }

    private function isWebApiRequest(): bool
    {
        try {
            $areaCode = $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            return false;
        }

        return in_array($areaCode, [Area::AREA_WEBAPI_REST, Area::AREA_WEBAPI_SOAP], true);
    }
}
