<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Plugin;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;

class AddConfigDataToCart
{
    private Config $config;
    private CompositeConfigProvider $compositeConfigProvider;
    private CheckoutSession $checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        Config $config,
        CompositeConfigProvider $compositeConfigProvider
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->compositeConfigProvider = $compositeConfigProvider;
    }

    public function afterGetSectionData(Cart $subject, array $result): array
    {
        $hasQuote = $this->getQuote() && $this->getQuote()->getId();
        if(!$hasQuote) {
            // no quote yet, preventing 400 error on new session
            return $result;
        }
        $configProvider = ['checkoutConfigProvider' => $this->compositeConfigProvider->getConfig()];
        return array_merge($this->config->getMethodsConfig(), $configProvider, $result);

    }

    /**
     * Get active quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }
}
