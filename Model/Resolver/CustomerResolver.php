<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
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
    /**
     * Name of the request parameter carrying the guest email known client-side.
     */
    public const GUEST_EMAIL_PARAM_NAME = 'flow_guest_email';

    /**
     * Key under which the guest email is passed down to the payment session request.
     */
    public const GUEST_EMAIL_DATA_KEY = 'guest_email';

    protected $customerFactory;

    public function __construct(
        CustomerInterfaceFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param CartInterface $quote
     * @param string|null   $guestEmail Email known client-side but not yet persisted on the quote.
     *
     * @return CustomerInterface
     */
    public function resolve(CartInterface $quote, ?string $guestEmail = null): CustomerInterface
    {
        $customer = $quote->getCustomer();
        if (!empty($customer->getEmail()) && !empty($customer->getFirstname()) && !empty($customer->getLastname())) {
            return $customer;
        }

        $newCustomer = $this->customerFactory->create();
        $billingAddress = $quote->getBillingAddress();
        $newCustomer->setFirstname($billingAddress->getFirstname());
        $newCustomer->setLastname($billingAddress->getLastname());
        $newCustomer->setEmail(!empty($billingAddress->getEmail()) ? $billingAddress->getEmail() : $guestEmail);

        return $newCustomer;
    }
}
