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

namespace CheckoutCom\Magento2\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;

class CustomerResolver
{
    protected $customerFactory;

    public function __construct(
        CustomerInterfaceFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;
    }

    public function resolve(CartInterface $quote): CustomerInterface
    {
        $customer = $quote->getCustomer();
        if (!empty($customer->getEmail()) && !empty($customer->getFirstname()) && !empty($customer->getLastname())) {
            return $customer;
        }

        $newCustomer = $this->customerFactory->create();
        $billingAddress = $quote->getBillingAddress();
        $newCustomer->setFirstname($billingAddress->getFirstname());
        $newCustomer->setLastname($billingAddress->getLastname());
        $newCustomer->setEmail($billingAddress->getEmail());

        return $newCustomer;
    }
}
