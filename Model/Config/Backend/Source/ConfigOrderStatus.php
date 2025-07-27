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
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;

/**
 * Class ConfigOrderStatus
 */
class ConfigOrderStatus implements OptionSourceInterface
{
    public function __construct(
        private Collection $orderStatusCollection
    ) {
    }

    /**
     * Return the order status options
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return $this->getStatusOptions();
    }

    /**
     * Get the order status options
     *
     * @return string[][]
     */
    public function getStatusOptions(): array
    {
        // Return the options as array
        return $this->orderStatusCollection->toOptionArray();
    }
}
