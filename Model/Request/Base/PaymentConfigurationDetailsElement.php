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

use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentConfigurationDetails;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentConfigurationDetailsFactory;
use CheckoutCom\Magento2\Model\Request\Base\AccountHolderElement;
use Magento\Customer\Api\Data\CustomerInterface;

class PaymentConfigurationDetailsElement
{
    protected PaymentConfigurationDetailsFactory $modelFactory;
    protected AccountHolderElement $accountHolderElement;

    public function __construct(
        PaymentConfigurationDetailsFactory $modelFactory,
        AccountHolderElement $accountHolderElement
    ) {
        $this->modelFactory = $modelFactory;
        $this->accountHolderElement = $accountHolderElement;
    }

    public function get(CustomerInterface $customer): PaymentConfigurationDetails
    {
        $model = $this->modelFactory->create();

        $model->store_payment_details = "disabled";
        $model->account_holder = $this->accountHolderElement->get($customer);

        return $model;
    }
}
