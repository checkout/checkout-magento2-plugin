<?php

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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request\PaymentMethodConfiguration;

use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentMethodConfiguration;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentMethodConfigurationFactory;
use CheckoutCom\Magento2\Model\Request\Base\PaymentConfigurationDetailsElement;
use Magento\Customer\Api\Data\CustomerInterface;

class PaymentMethodConfigurationElement
{
    protected PaymentMethodConfigurationFactory $modelFactory;
    protected PaymentConfigurationDetailsElement $paymentDetailsElement;

    public function __construct(
        PaymentMethodConfigurationFactory $modelFactory,
        PaymentConfigurationDetailsElement $paymentDetailsElement
    ) {
        $this->modelFactory = $modelFactory;
        $this->paymentDetailsElement = $paymentDetailsElement;
    }

    public function get(CustomerInterface $customer): PaymentMethodConfiguration
    {
        $model = $this->modelFactory->create();

        $model->card = $this->paymentDetailsElement->get($customer);
        $model->applepay = $this->paymentDetailsElement->get($customer);
        $model->googlepay = $this->paymentDetailsElement->get($customer);

        return $model;
    }
}
