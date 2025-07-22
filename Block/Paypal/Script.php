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
namespace CheckoutCom\Magento2\Block\Paypal;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

/**
 * Class Script
 */
class Script extends Template
{
    public function __construct(
        private PaypalMethod $paypalMethod,
        private Config $checkoutConfig,
        TemplateContext $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPaypalMerchantId(): string
    {
        return (string)$this->paypalMethod->getConfigData('merchant_id');
    }

    public function getClientId(): string
    {
        return (string)$this->paypalMethod->getConfigData('checkout_client_id');
    }

    public function getPartnerAttributionId(): string
    {
        return (string)$this->paypalMethod->getConfigData('checkout_partner_attribution_id');
    }

    public function getIntent(): string
    {
        return $this->checkoutConfig->needsAutoCapture() ? 'capture' : 'authorize';
    }

    public function getCommit(): string
    {
        return $this->isExpressButton() ? 'false' : 'true';
    }

    public function getPageType(): string
    {
        return $this->getScriptType() ?? 'checkout';
    }

    private function isExpressButton(): bool
    {
        return $this->getMode() === 'express';
    }
}
