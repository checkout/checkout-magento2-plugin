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

namespace CheckoutCom\Magento2\Model\Request\Billing;

use Checkout\Payments\BillingInformation;
use Checkout\Payments\BillingInformationFactory;
use CheckoutCom\Magento2\Model\Request\Base\AddressElement;
use CheckoutCom\Magento2\Model\Request\Base\PhoneElement;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;

class BillingElement
{
    protected BillingInformationFactory $modelFactory;
    protected AddressElement $addressElement;
    protected PhoneElement $phoneElement;

    public function __construct(
        BillingInformationFactory $modelFactory,
        AddressElement $addressElement,
        PhoneElement $phoneElement
    ) {
        $this->modelFactory = $modelFactory;
        $this->addressElement = $addressElement;
        $this->phoneElement = $phoneElement;
    }

    /**
     * @param QuoteAddressInterface|OrderAddressInterface $billingAddress
     *
     * @return BillingInformation
     */
    public function get($billingAddress): BillingInformation
    {
        $model = $this->modelFactory->create();

        $model->address = $this->addressElement->get($billingAddress);

        $phone = $this->phoneElement->get($billingAddress->getCountryId(), $billingAddress->getTelephone());

        if ($phone) {
            $model->phone = $phone;
        }

        return $model;
    }
}
