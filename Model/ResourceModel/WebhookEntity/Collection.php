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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    public $_idFieldName = 'id';

    /**
     * Define the resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \CheckoutCom\Magento2\Model\WebhookEntity::class,
            \CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity::class
        );
    }
}
