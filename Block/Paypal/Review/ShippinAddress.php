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

use Magento\Checkout\Model\Session;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\View\Element\Template;

class ShippinAddress extends Template
{
    protected Session $checkoutSession;
    protected AddressConfig $addressConfig;

    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        AddressConfig $addressConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->addressConfig = $addressConfig;
        $this->checkoutSession = $checkoutSession;
    }

    public function renderAddress(): string
    {
        /** @var RendererInterface $renderer */
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        $addressData = ConvertArray::toFlatArray($this->checkoutSession->getQuote()->getShippingAddress()->getData());

        return $renderer->renderArray($addressData);
    }
}
