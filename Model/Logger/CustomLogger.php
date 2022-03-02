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

namespace CheckoutCom\Magento2\Model\Logger;

use Magento\Framework\Logger\Handler\Debug;
use Monolog\Logger;

class CustomLogger extends Debug
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/checkoutcom_magento2.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
