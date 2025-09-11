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

namespace CheckoutCom\Magento2\Model\Request\Base;

use CheckoutCom\Magento2\Model\Request\Additionnals\AccountHolder;
use CheckoutCom\Magento2\Model\Request\Additionnals\AccountHolderFactory;
use Magento\Customer\Api\Data\CustomerInterface;

class AccountHolderElement
{
    protected AccountHolderFactory $modelFactory;

    public function __construct(
        AccountHolderFactory $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function get(CustomerInterface $customer): AccountHolder
    {
        $model = $this->modelFactory->create();

        $model->type = "individual";
        $model->first_name = $customer->getFirstName();
        $model->last_name = $customer->getLastname();

        return $model;
    }
}
