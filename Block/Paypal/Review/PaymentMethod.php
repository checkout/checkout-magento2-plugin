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

use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;

class PaymentMethod extends Template
{
    protected Session $checkoutSession;
    protected PaypalMethod $paypalMethod;

    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        PaypalMethod $paypalMethod,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->paypalMethod = $paypalMethod;
    }

    public function getEmail(): string
    {
        return (string)$this->checkoutSession->getQuote()->getCustomerEmail();
    }

    public function getPaymentMethod(): string
    {
        return (string)$this->paypalMethod->getTitle();
    }
}
