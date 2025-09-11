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

namespace CheckoutCom\Magento2\Model\Request\Sender;

use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentSenderFactory;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentSender;
use Magento\Customer\Api\Data\CustomerInterface;

class SenderElement
{
    protected PaymentSenderFactory $modelFactory;

    public function __construct(
        PaymentSenderFactory $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function get(CustomerInterface $customer): PaymentSender
    {
        $model = $this->modelFactory->create(["type" => "individual"]);

        $model->first_name = $customer->getFirstname();
        $model->last_name = $customer->getLastname();

        return $model;
    }
}
