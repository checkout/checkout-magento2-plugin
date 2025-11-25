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

use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentCustomerRequest;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentCustomerRequestFactory;
use CheckoutCom\Magento2\Model\Request\Base\PhoneElement;
use CheckoutCom\Magento2\Model\Request\Base\SummaryElement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;

class CustomerElement
{
    protected PaymentCustomerRequestFactory $modelFactory;
    protected PhoneElement $phoneElement;
    protected SummaryElement $summaryElement;

    public function __construct(
        PaymentCustomerRequestFactory $modelFactory,
        PhoneElement $phoneElement,
        SummaryElement $summaryElement,
    ) {
        $this->modelFactory = $modelFactory;
        $this->phoneElement = $phoneElement;
        $this->summaryElement = $summaryElement;
    }

    public function get(CustomerInterface $customer, QuoteAddressInterface|OrderAddressInterface $billingAddress): PaymentCustomerRequest
    {
        $model = $this->modelFactory->create();

        $model->email = $customer->getEmail();
        $model->name = $customer->getFirstname() . ' ' . $customer->getLastname();

        $phone = $billingAddress->getTelephone();
        $country = $billingAddress->getCountryId();
        $model->phone = $this->phoneElement->get($country, $phone);

        return $model;
    }

    public function fillSummary(PaymentCustomerRequest $model, CustomerInterface $customer, string $currency): void
    {
        $model->summary = $this->summaryElement->get($customer, $currency);
    }
}
