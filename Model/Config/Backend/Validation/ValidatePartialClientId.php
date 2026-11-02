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

namespace CheckoutCom\Magento2\Model\Config\Backend\Validation;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class ValidatePartialClientId extends Value
{
    /**
     * @throws LocalizedException
     */
    public function beforeSave(): Value
    {
        $value = (string)$this->getValue();

        if ($value !== '' && !preg_match('/^[a-zA-Z0-9]{1,8}$/', $value)) {
            throw new LocalizedException(
                __('The Partial Client ID must contain only alphanumeric characters (8 characters maximum).')
            );
        }

        return parent::beforeSave();
    }
}
