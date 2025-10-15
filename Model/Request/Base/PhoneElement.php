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

use Checkout\Common\Phone;
use Checkout\Common\PhoneFactory;

class PhoneElement
{
    protected PhoneFactory $modelFactory;

    public function __construct(
        PhoneFactory $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function get(string $countryCode, string $number): Phone
    {
        $model = $this->modelFactory->create();

        $model->country_code = $countryCode;
        $model->number = $number;

        return $model;
    }
}
