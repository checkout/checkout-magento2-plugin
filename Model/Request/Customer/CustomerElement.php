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

namespace CheckoutCom\Magento2\Model\Request\Customer;

use Checkout\Payments\PaymentCustomerRequest;
use Checkout\Payments\PaymentCustomerRequestFactory;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerElement
{
    protected PaymentCustomerRequestFactory $modelFactory;

    public function __construct(
        PaymentCustomerRequestFactory $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function get(CustomerInterface $customer): PaymentCustomerRequest
    {
        $model = $this->modelFactory->create();

        $model->email = $customer->getEmail();
        $model->name = $customer->getFirstname() . ' ' . $customer->getLastname();

        return $model;
    }
}
