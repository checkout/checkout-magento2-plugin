<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request\Additionnals;

use Checkout\Payments\Links\PaymentLinkRequest as BasePaymentLinkRequest;

class PaymentLinkRequest extends BasePaymentLinkRequest
{
    /**
     * @var array of PaymentSourceType
     */
    public $disabled_payment_methods;
}
