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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class ApmRequestValidator
 *
 * Checks the values an alternative payment method request receives from the browser.
 */
class ApmRequestValidator
{
    private const PRODUCTION_HOST = 'api.checkout.com';
    private const SANDBOX_HOST = 'api.sandbox.checkout.com';
    private const MANDATE_PATH = '#\A/sepa/mandates/(src_[a-z0-9]{26})\z#';
    private const PREFIX = '#\A[a-z0-9]{1,63}\z#';

    /**
     * Returns the source id of a SEPA mandate URL, or null when the URL is not a mandate URL of
     * the configured Checkout.com environment.
     *
     * @param string $url Mandate URL received from the browser.
     * @param bool $sandbox Whether the store uses the sandbox environment.
     * @param string|null $prefix Merchant subdomain prefix configured for the store, if any.
     *
     * @return string|null
     */
    public function getMandateSourceId(string $url, bool $sandbox, ?string $prefix): ?string
    {
        $base = $sandbox ? self::SANDBOX_HOST : self::PRODUCTION_HOST;
        $hosts = [$base];
        $prefix = $prefix === null ? '' : strtolower(trim($prefix));
        if ($prefix !== '' && preg_match(self::PREFIX, $prefix) === 1) {
            $hosts[] = $prefix . '.' . $base;
        }

        foreach ($hosts as $host) {
            $origin = 'https://' . $host;
            if (strncmp($url, $origin . '/', strlen($origin) + 1) !== 0) {
                continue;
            }
            if (preg_match(self::MANDATE_PATH, substr($url, strlen($origin)), $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Whether a method is in the comma-separated list of methods enabled in the store config.
     *
     * @param string $method Method requested by the browser.
     * @param string|null $enabledMethods Comma-separated list from the store config.
     *
     * @return bool
     */
    public function isEnabledMethod(string $method, ?string $enabledMethods): bool
    {
        if ($method === '' || $enabledMethods === null) {
            return false;
        }

        return in_array($method, array_map('trim', explode(',', $enabledMethods)), true);
    }
}
