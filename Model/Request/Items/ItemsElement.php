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

namespace CheckoutCom\Magento2\Model\Request\Items;

use Checkout\Payments\Product;
use Checkout\Payments\ProductFactory;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use CheckoutCom\Magento2\Provider\CurrenciesSettings;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\StoreManagerInterface;

class ItemsElement
{
    protected ProductFactory $modelFactory;
    protected PriceFormatter $priceFormatter;
    protected CurrenciesSettings $currenciesSettings;

    private StoreManagerInterface $storeManager;
    private Utilities $utilities;

    public function __construct(
        ProductFactory $modelFactory,
        CurrenciesSettings $currenciesSettings,
        StoreManagerInterface $storeManager,
        PriceFormatter $priceFormatter,
        Utilities $utilities
    ) {
        $this->modelFactory = $modelFactory;
        $this->currenciesSettings = $currenciesSettings;
        $this->storeManager = $storeManager;
        $this->priceFormatter = $priceFormatter;
        $this->utilities = $utilities;
    }

    public function get(CartInterface $quote): array
    {
        /**
         * @var Product[]
         */
        $items = [];
        $quoteItems = $quote->getItems();

        if (empty($quoteItems)) {
            return [];
        }

        $currency = $this->getQuoteCurrency($quote);

        /** @var CartItemInterface $item */
        foreach ($quoteItems as $item) {
            $product = $this->getLineItem($item, $currency);
            if (!$product) {
                continue;
            }
            $items[] = $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $items[] = $this->getShippingItem($shipping, $currency);
        }

        $this->adjustItemsTotals($items);

        return $items;
    }

    /**
     * When a product has discount, discount amount must be set at unit price level,
     * Sometimes this division is not an int price, giving a checkout api error
     * Adjust it by adding a "fake" item in order to have a correct api response
     */
    private function adjustItemsTotals(array &$items): void
    {
        $adjustment = 0;
        foreach ($items as $item) {
            if (str_contains((string)$item->unit_price, '.')) {
                $currentTotal = $item->total_amount;
                $item->total_amount = (int)$item->unit_price * $item->quantity;
                $item->unit_price = (int)$item->unit_price;
                $adjustment += $currentTotal - $item->total_amount;
            }
        }
        if ($adjustment > 0) {
            $product = $this->modelFactory->create();
            $product->quantity = 1;
            $product->total_amount = $adjustment;
            $product->unit_price = $adjustment;
            $product->name = "CheckoutCom total adjustment";
            $product->sku = "CKO_ADJUST";
            $items[] = $product;
        }
    }

    protected function getLineItem(CartItemInterface $item, string $currency): ?Product
    {
        $product = $this->modelFactory->create();

        if (!$item->getQty()) {
            return null;
        }

        $discount = $discountOnUnitPrice = $this->utilities->formatDecimals($item->getDiscountAmount()) * 100;
        $rowAmount = ($this->utilities->formatDecimals($item->getRowTotalInclTax()) * 100) -
            ($this->utilities->formatDecimals($discount));
        $unitPrice = ($this->utilities->formatDecimals($item->getRowTotalInclTax() / $item->getQty()) * 100) -
            ($this->utilities->formatDecimals($discountOnUnitPrice / $item->getQty()));
        // Api does not accept 0 prices
        if (!$unitPrice) {
            return null;
        }

        $product->name = $item->getName();
        $product->quantity = $item->getQty();
        $product->reference = $item->getSku();
        $product->unit_price = $unitPrice;
        $product->total_amount = $rowAmount;

        return $product;
    }

    protected function getShippingItem(Address $shipping, string $currency): Product
    {
        $product = $this->modelFactory->create();

        $product->name = $shipping->getShippingDescription();
        $product->quantity = 1;
        $product->unit_price = $this->priceFormatter->getFormattedPrice($shipping->getShippingInclTax(), $currency);
        $product->total_amount = $this->priceFormatter->getFormattedPrice($shipping->getShippingAmount(), $currency);

        return $product;
    }

    protected function getQuoteCurrency(CartInterface $quote): string
    {
        $quoteCurrencyCode = $quote->getQuoteCurrencyCode();

        if (!empty($quoteCurrencyCode)) {
            return $quoteCurrencyCode;
        }

        try {
            $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (\Throwable $th) {
            $storeCurrencyCode = '';
        }

        return $storeCurrencyCode;

    }
}
