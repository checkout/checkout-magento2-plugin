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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Methods;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Flow-based Apple Pay as a standalone Magento payment method (its own radio in the
 * payment list). Reuses all of FlowMethod's capture/void/refund logic; only the payment
 * method code differs so Magento treats it as a separate method.
 */
class ApplePayFlowMethod extends FlowMethod
{
    /**
     * CODE constant
     *
     * @var string CODE
     */
    public const CODE = 'checkoutcom_flow_apple_pay';

    /**
     * $code field
     *
     * @var string $code
     */
    protected $code = self::CODE;

    /**
     * Available only when Apple Pay is enabled (payment/checkoutcom_apple_pay/active) AND configured
     * to display outside the Flow (payment/checkoutcom_apple_pay/flow_standalone), in addition to the
     * Flow availability checks. When flow_standalone is off, Apple Pay is rendered inside the
     * "Pay with Checkout.com" method instead, so this standalone method must not appear.
     *  Available only when: Apple Pay is active, flow_standalone is enabled, flow_enabled_on_all_browsers
     *  is enabled, and all three page-level placement flags (checkout, cart, minicart) are enabled.
     *  When flow_standalone is off, Apple Pay renders inside "Pay with Checkout.com" instead.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(?CartInterface $quote = null): bool
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        $websiteCode = $this->storeManager->getWebsite()->getCode();

        return $this->flowPaymentMethodSettings->isApplePayEnabled($websiteCode, true)
            && $this->flowPaymentMethodSettings->isApplePayFlowStandalone($websiteCode)
            && $this->flowPaymentMethodSettings->isFlowApplePayEnabledOnAllBrowsers($websiteCode)
            && $this->flowPaymentMethodSettings->isApplePayEnabledOnCheckout($websiteCode)
            && $this->flowPaymentMethodSettings->isApplePayEnabledOnCart($websiteCode)
            && $this->flowPaymentMethodSettings->isApplePayEnabledOnMiniCart($websiteCode);
    }
}
