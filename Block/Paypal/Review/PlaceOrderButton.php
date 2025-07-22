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
use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context as TemplateContext;

/**
 * Class PlaceOrderButton
 */
class PlaceOrderButton extends Template
{
    public function __construct(
        protected Session $checkoutSession,
        protected RequestInterface $request,
        protected PaypalMethod $paypalMethod,
        TemplateContext $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEmail(): string
    {
        return (string)$this->checkoutSession->getQuote()->getCustomerEmail();
    }

    public function canPlaceOrder(): bool
    {
        return (bool)$this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
    }

    public function getPaymentMethod(): string
    {
        return (string)$this->paypalMethod->getCode();
    }

    public function getContextId(): string
    {
        return (string)$this->request->getParam(Review::PAYMENT_CONTEXT_ID_PARAMETER);
    }
}
