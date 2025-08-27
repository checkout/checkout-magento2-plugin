<?php

namespace CheckoutCom\Magento2\Model\Request\Additionnals;

use Checkout\Payments\Sessions\PaymentMethodConfiguration as BasePaymentMethodConfiguration;

class PaymentMethodConfiguration extends BasePaymentMethodConfiguration
{
    /**
     * @var PaymentConfigurationDetails
     */
    public $card;

    /**
     * @var PaymentConfigurationDetails
     */
    public $googlepay;

    /**
     * @var PaymentConfigurationDetails
     */
    public $applepay;
}
