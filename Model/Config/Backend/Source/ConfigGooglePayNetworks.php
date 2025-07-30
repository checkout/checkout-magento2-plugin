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
 * Class ConfigGooglePayNetworks
 */
class ConfigGooglePayNetworks implements OptionSourceInterface
{
    /**
     * CARD_VISA string
     *
     * @var string CARD_VISA
     */
    const CARD_VISA = 'VISA';
    /**
     * CARD_MASTERCARD string
     *
     * @var string CARD_MASTERCARD
     */
    const CARD_MASTERCARD = 'MASTERCARD';
    /**
     * CARD_AMEX string
     *
     * @var string CARD_AMEX
     */
    const CARD_AMEX = 'AMEX';
    /**
     * CARD_JCB string
     *
     * @var string CARD_JCB
     */
    const CARD_JCB = 'JCB';
    /**
     * CARD_DISCOVER string
     *
     * @var string CARD_DISCOVER
     */
    const CARD_DISCOVER = 'DISCOVER';

    /**
     * Possible Google Pay Cards
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::CARD_VISA,
                'label' => __('Visa')
            ],
            [
                'value' => self::CARD_MASTERCARD,
                'label' => __('Mastercard')
            ],
            [
                'value' => self::CARD_AMEX,
                'label' => __('American Express')
            ],
            [
                'value' => self::CARD_JCB,
                'label' => __('JCB')
            ],
            [
                'value' => self::CARD_DISCOVER,
                'label' => __('Discover')
            ],
        ];
    }
}
