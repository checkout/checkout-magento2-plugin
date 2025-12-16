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

namespace CheckoutCom\Magento2\Model\Request\Additionnals;

class Summary
{
    /**
     * @var string
    */
    public $first_transaction_date;
    
    /**
     * @var bool
     */
    public $is_premium_customer;

    /**
     * @var bool
     */
    public $is_returning_customer;

    /**
     * @var float
     */
    public $last_payment_amount;

    /**
     * @var string
    */
    public $last_payment_date;

    /**
     * @var float
     */
    public $lifetime_value;

    /**
     * @var string
     */
    public $registration_date;

    /**
     * @var int
     */
    public $total_order_count;

}
