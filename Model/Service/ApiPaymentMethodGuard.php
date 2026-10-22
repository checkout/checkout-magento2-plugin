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

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Checks\SpecificationFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Allow only active and available Checkout.com payment methods on the payment APIs
 */
class ApiPaymentMethodGuard
{
    private const VAULT_METHOD = 'checkoutcom_vault';

    public function __construct(
        private readonly MethodHandlerService $methodHandler,
        private readonly SpecificationFactory $methodSpecificationFactory
    ) {
    }

    /**
     * True when the method is a Checkout.com method able to process an API payment,
     * active and available for the quote. Any other method (e.g. Magento "free") is refused
     * so that no order can be placed without a Checkout.com payment.
     *
     * @param string $methodId
     * @param CartInterface $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAllowed(string $methodId, CartInterface $quote): bool
    {
        if (!$this->methodHandler->has($methodId)) {
            return false;
        }

        $method = $this->methodHandler->get($methodId);
        if (!method_exists($method, 'sendPaymentRequest')) {
            return false;
        }

        if (!$this->isAvailable($methodId, $method, $quote)) {
            return false;
        }

        return $this->methodSpecificationFactory->create([
            MethodInterface::CHECK_USE_FOR_COUNTRY,
            MethodInterface::CHECK_USE_FOR_CURRENCY,
            MethodInterface::CHECK_ORDER_TOTAL_MIN_MAX,
            MethodInterface::CHECK_ZERO_TOTAL,
        ])->isApplicable($method, $quote);
    }

    /**
     * Check the method availability for the quote
     *
     * @param string $methodId
     * @param MethodInterface $method
     * @param CartInterface $quote
     *
     * @return bool
     * @throws LocalizedException
     */
    private function isAvailable(string $methodId, MethodInterface $method, CartInterface $quote): bool
    {
        // The vault availability relies on the storefront customer session, which an API call does not have:
        // require a customer quote instead, the card ownership being checked against the public hash
        if ($methodId === self::VAULT_METHOD) {
            return (int)$quote->getCustomerId() > 0 && $method->isActive((int)$quote->getStoreId());
        }

        return $method->isAvailable($quote);
    }
}
