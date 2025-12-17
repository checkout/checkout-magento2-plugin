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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Block\Cart;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class CheckoutConfig
 */
class CheckoutConfig extends Onepage
{
    private Cart $cart;
    private ConfigProvider $checkoutComConfigProvider;
    private SerializerInterface $serializer;
    private Config $checkoutComConfig;

    public function __construct(
        Cart $cart,
        Context $context,
        FormKey $formKey,
        CompositeConfigProvider $configProvider,
        Config $checkoutComConfig,
        ConfigProvider $checkoutComConfigProvider,
        array $layoutProcessors = [],
        array $data = [],
        ?Json $serializer = null,
        ?SerializerInterface $serializerInterface = null
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
        $this->checkoutComConfigProvider = $checkoutComConfigProvider;
        $this->checkoutComConfig = $checkoutComConfig;
        $this->serializer = $serializerInterface ?: ObjectManager::getInstance()->get(JsonHexTag::class);
    }

    public function getProductCount(): int
    {
        $productCount = 0;
        if ($this->cart->getQuote()->getItemsCollection()->getSize() > 0) {
            $productCount = 1;
        }

        return $productCount;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getSerializedCheckoutComConfig(): string
    {
        $config = $this->checkoutComConfigProvider->getConfig();

        return $this->serializer->serialize($config);
    }

    public function isPaypalOrApplePayEnabled(): bool
    {
        return $this->checkoutComConfig->getValue('active', 'checkoutcom_apple_pay')
               || $this->checkoutComConfig->getValue('active', 'checkoutcom_paypal');
    }
}
