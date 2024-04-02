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
namespace CheckoutCom\Magento2\Block\Paypal\Review;

use CheckoutCom\Magento2\Controller\Paypal\Review;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Quote\Model\Quote\Address as QuoteAddresss;
use Magento\Quote\Model\Quote\Address\Rate as QuoteRate;

class ShippingMethod extends Template
{
    protected Session $checkoutSession;
    protected UrlInterface $url;
    protected RequestInterface $request;
    protected PriceCurrencyInterface $priceCurrency;

    public function __construct(
        TemplateContext $context,
        Session $checkoutSession,
        RequestInterface $request,
        UrlInterface $url,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
        $this->request = $request;
        $this->priceCurrency = $priceCurrency;
    }

    public function getRates(): array
    {
        $shippingAddress = $this->getShippingAddress();
        $shippingAddress->collectShippingRates();

        return $shippingAddress->getGroupedAllShippingRates();
    }

    public function getShippingMethodUpdateUrl(QuoteRate $carrierRate): string
    {
        return $this->url->getUrl('checkoutcom/paypal/saveExpressShippingMethod', [
            Review::PAYMENT_CONTEXT_ID_PARAMETER => $this->request->getParam(Review::PAYMENT_CONTEXT_ID_PARAMETER),
            Review::SHIPPING_METHOD_PARAMETER => $carrierRate->getCode()
        ]);
    }

    public function isCurrentShippingRate(QuoteRate $carrierRate): bool
    {
        return $carrierRate->getCode() === $this->getShippingAddress()->getShippingMethod();
    }

    public function getFormatedPrice($price): string
    {
        return $this->priceCurrency->convertAndFormat(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->checkoutSession->getQuote()->getStore()
        );
    }

    private function getShippingAddress(): QuoteAddresss
    {
        return $this->checkoutSession->getQuote()->getShippingAddress();
    }
}
