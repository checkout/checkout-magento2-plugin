<?php

declare(strict_types=1);

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

namespace CheckoutCom\Magento2\Block\Cart;

use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;

class ApplePay extends Onepage
{
    protected Cart $cart;

    /**
     * @param Cart $cart
     * @param Context $context
     * @param FormKey $formKey
     * @param CompositeConfigProvider $configProvider
     * @param array $layoutProcessors
     * @param array $data
     * @param Json|null $serializer
     * @param SerializerInterface|null $serializerInterface
     */
    public function __construct(
        Cart $cart,
        Context $context,
        FormKey $formKey,
        CompositeConfigProvider $configProvider,
        array $layoutProcessors = [],
        array $data = [],
        Json $serializer = null,
        SerializerInterface $serializerInterface = null
    ) {
        parent::__construct(
            $context,
            $formKey,
            $configProvider,
            $layoutProcessors,
            $data,
            $serializer,
            $serializerInterface
        );
        $this->cart = $cart;
    }

    /**
     *
     * @return int
     */
    public function getProductCount(): int
    {
        $productCount = 0;
        if ($this->cart->getQuote()->getItemsCollection()->getSize() > 0) {
            $productCount = 1;
        }

        return $productCount;
    }
}
