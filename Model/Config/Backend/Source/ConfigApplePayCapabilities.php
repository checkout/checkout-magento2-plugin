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

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ConfigApplePayCapabilities
 */
class ConfigApplePayCapabilities implements OptionSourceInterface
{
    /**
     * CAP_CRE constant
     *
     * @var string CAP_CRE
     */
    const CAP_CRE = 'supportsCredit';
    /**
     * CAP_DEB constant
     *
     * @var string CAP_DEB
     */
    const CAP_DEB = 'supportsDebit';

    /**
     * Possible Apple Pay Cards
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::CAP_CRE,
                'label' => __('Credit cards')
            ],
            [
                'value' => self::CAP_DEB,
                'label' => __('Debit cards')
            ],
        ];
    }
}
