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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolve the quote targeted by a payment API call, enforcing that the caller owns it
 */
class ApiQuoteResolver
{
    private const MASKED_ID_PATTERN = '/^[A-Za-z0-9]{32}$/';

    public function __construct(
        private readonly QuoteHandlerService $quoteHandler,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly QuoteIdMaskResource $quoteIdMaskResource,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Active quote of the current website owned by the caller, or null when none matches.
     * Any store view of the website is accepted, as Magento shares a quote between them.
     * Guests ($customerId null) must provide a masked quote id; a raw numeric id is only
     * accepted for an authenticated customer, and the quote must belong to that customer.
     *
     * @param mixed $quoteId
     * @param int|null $customerId
     *
     * @return CartInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function resolve($quoteId, ?int $customerId): ?CartInterface
    {
        $entityId = $this->getEntityId((string)$quoteId, $customerId);
        if ($entityId < 1) {
            return null;
        }

        $quote = $this->quoteHandler->getQuote([
            'entity_id' => $entityId,
            'is_active' => 1,
            'store_id' => ['in' => $this->storeManager->getStore()->getWebsite()->getStoreIds()],
        ]);

        if (!$this->quoteHandler->isQuote($quote) || (int)$quote->getCustomerId() !== (int)$customerId) {
            return null;
        }

        return $quote;
    }

    /**
     * Convert the provided quote id to a quote entity id, 0 when it is not acceptable for the caller
     *
     * @param string $quoteId
     * @param int|null $customerId
     *
     * @return int
     */
    private function getEntityId(string $quoteId, ?int $customerId): int
    {
        if (preg_match(self::MASKED_ID_PATTERN, $quoteId)) {
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $this->quoteIdMaskResource->load($quoteIdMask, $quoteId, 'masked_id');

            return (int)$quoteIdMask->getQuoteId();
        }

        if ($customerId !== null && ctype_digit($quoteId)) {
            return (int)$quoteId;
        }

        return 0;
    }
}
