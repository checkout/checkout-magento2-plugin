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
use CheckoutCom\Magento2\Provider\CurrenciesSettings;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
/**
 * Class ItemsElement
*/
class ItemsElement
{
    protected ProductFactory $modelFactory;
    protected PriceFormatter $priceFormatter;
    protected CurrenciesSettings $currenciesSettings;

    private StoreManagerInterface $storeManager;
    
    public function __construct(
        ProductFactory $modelFactory,
        CurrenciesSettings $currenciesSettings,
        StoreManagerInterface $storeManager,
        PriceFormatter $priceFormatter
    ) {
        $this->modelFactory = $modelFactory;
        $this->currenciesSettings = $currenciesSettings;
        $this->storeManager = $storeManager;
        $this->priceFormatter = $priceFormatter;
    }

    public function get(CartInterface $quote): array {
        /**
         * @var Product[]
         */
        $items = [];

        if(empty($quote->getItems())) {
            return [];
        }

        $currency = $this->getQuoteCurrency($quote);

        /** @var CartItemInterface $item */
        foreach ($quote->getItems() as $item) {
            $product = $this->modelFactory->create();

            $unitPrice = $this->priceFormatter->getFormattedPrice($item->getPriceInclTax(), $currency);
            $linePrice = $this->priceFormatter->getFormattedPrice($item->getPriceInclTax(), $currency);
            $discount = $this->priceFormatter->getFormattedPrice($item->getDiscountAmount(), $currency);
  

            $product->name = $item->getName();
            $product->unit_price = $unitPrice;
            $product->quantity = $item->getQty();
            $product->reference = $item->getSku();
            $product->total_amount = $linePrice;

            $product->discount_amount = $discount;
            $items[] = $product;
        }

        // Shipping fee
        $shipping = $quote->getShippingAddress();

        if ($shipping->getShippingDescription()) {
            $product = $this->modelFactory->create();
            $product->name = $shipping->getShippingDescription();
            $product->quantity = 1;
            $product->unit_price = $this->priceFormatter->getFormattedPrice($shipping->getShippingInclTax(), $currency);
            $product->total_amount = $this->priceFormatter->getFormattedPrice($shipping->getShippingAmount(), $currency);

            $items[] = $product;
        }

        return $items;
    }

    protected function getQuoteCurrency(CartInterface $quote): string
    {
        $quoteCurrencyCode = $quote->getQuoteCurrencyCode();
        $storeCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        return ($quoteCurrencyCode) ?: $storeCurrencyCode;
    }
}
