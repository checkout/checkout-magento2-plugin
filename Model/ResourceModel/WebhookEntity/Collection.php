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

namespace CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity;

use CheckoutCom\Magento2\Model\Entity\WebhookEntity as WebhookEntityModel;
use CheckoutCom\Magento2\Model\ResourceModel\WebhookEntity as WebhookEntityResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * $_idFieldName field
     *
     * @var string $_idFieldName
     */
    protected $_idFieldName = 'id';

    /**
     * Define the resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            WebhookEntityModel::class,
            WebhookEntityResource::class
        );
    }
}
