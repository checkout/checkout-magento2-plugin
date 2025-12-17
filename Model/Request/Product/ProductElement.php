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

namespace CheckoutCom\Magento2\Model\Request\Product;

use Checkout\Payments\Product;
use Checkout\Payments\ProductFactory;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Formatter\PriceFormatter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class ProductElement
{
    protected ProductFactory $modelFactory;
    protected PriceFormatter $priceFormatter;
    private Utilities $utilities;

    public function __construct(
        ProductFactory $modelFactory,
        PriceFormatter $priceFormatter,
        Utilities $utilities
    ) {
        $this->modelFactory = $modelFactory;
        $this->priceFormatter = $priceFormatter;
        $this->utilities = $utilities;
    }

    public function get(OrderInterface $order): array
    {
        /**
         * @var Product[]
         */
        $items = [];
        $orderItems = $order->getAllVisibleItems();

        if (empty($orderItems)) {
            return [];
        }

        $currency = $order->getOrderCurrencyCode() ?? '';

        /** @var OrderItemInterface $item */
        foreach ($orderItems as $item) {
            $product = $this->getLineItem($item, $currency);
            if (!$product) {
                continue;
            }
            $items[] = $product;
        }

        return $items;
    }


    protected function getLineItem(OrderItemInterface $item, string $currency): ?Product
    {
        $product = $this->modelFactory->create();

        if (!$item->getQtyOrdered()) {
            return null;
        }

        $discount = $discountOnUnitPrice = $this->utilities->formatDecimals($item->getDiscountAmount());
        $rowAmount = ($this->utilities->formatDecimals($item->getRowTotalInclTax())) -
            ($this->utilities->formatDecimals($discount));
        $unitPrice = ($this->utilities->formatDecimals($item->getRowTotalInclTax() / $item->getQtyOrdered())) -
            ($this->utilities->formatDecimals($discountOnUnitPrice / $item->getQtyOrdered()));
        
        if (!$unitPrice) {
            return null;
        }

        $product->name = $item->getName();
        $product->quantity = $item->getQtyOrdered();
        $product->reference = $item->getSku();
        $product->unit_price = $this->priceFormatter->getFormattedPrice($unitPrice, $currency);
        $product->total_amount = $this->priceFormatter->getFormattedPrice($rowAmount, $currency);;

        return $product;
    }
}
