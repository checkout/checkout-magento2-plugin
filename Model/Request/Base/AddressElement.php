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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request\Base;

use Checkout\Common\Address;
use Checkout\Common\AddressFactory;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;

class AddressElement
{
    protected AddressFactory $modelFactory;

    public function __construct(
        AddressFactory $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * @param QuoteAddressInterface|OrderAddressInterface $address
     *
     * @return Address
     */
    public function get($address): Address
    {
        $model = $this->modelFactory->create();

        $model->address_line1 = $address->getStreetLine(1);
        $model->address_line2 = $address->getStreetLine(2);
        $model->city = $address->getCity();
        $model->country = $address->getCountryId();
        $model->zip = $address->getPostcode();
        $model->state = $address->getRegion();

        return $model;
    }
}
