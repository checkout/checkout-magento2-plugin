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

namespace CheckoutCom\Magento2\Provider;

use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;

/**
 * Class CardPaymentSettings
 */
class CardPaymentSettings extends AbstractSettingsProvider {

    public const CONFIG_THREE_DS = "payment/checkoutcom_card_payment/three_ds";
    public const CONFIG_ATTEMPT_N3D = "payment/checkoutcom_card_payment/attempt_n3d";

    public function isThreeDSEnabled(?string $website): bool {
        return $this->getWebsiteLevelConfiguration(
            $website,
            self::CONFIG_THREE_DS
        ) === "1";
    }

    public function isAttemptN3DEnabled(?string $website): bool {
        return $this->getWebsiteLevelConfiguration(
            $website,
            self::CONFIG_ATTEMPT_N3D
        ) === "1";
    }
}