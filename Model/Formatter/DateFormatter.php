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

namespace CheckoutCom\Magento2\Model\Formatter;

use Exception;
use Magento\Framework\Intl\DateTimeFactory;

class DateFormatter
{
    private $dateTimeFactory;
    
    public function __construct(
        DateTimeFactory $dateTimeFactory
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function getFormattedDate(string $date): string
    {
        if ($date === '') {
            return '';
        }

        try {
            $dateAsObject = $this->dateTimeFactory->create($date);

            return $dateAsObject->format('Y-m-d');
        } catch (Exception $e) {
            return '';
        }
    }
}
