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

namespace CheckoutCom\Magento2\Model\Request\Billing;

use Checkout\Payments\BillingInformation;
use Checkout\Payments\BillingInformationFactory;
use CheckoutCom\Magento2\Model\Request\Base\AddressElement;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class BillingElement
 */
class BillingElement
{
    protected BillingInformationFactory $modelFactory;

    protected AddressElement $addressElement;

    public function __construct(
        BillingInformationFactory $modelFactory,
        AddressElement $addressElement,
    ) {
        $this->modelFactory = $modelFactory;
        $this->addressElement = $addressElement;
    }

    public function get(AddressInterface $billingAddress): BillingInformation {
        $model = $this->modelFactory->create();

        $model->address = $this->addressElement->get($billingAddress);

        return $model;
    }
}
