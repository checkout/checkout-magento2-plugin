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

namespace CheckoutCom\Magento2\Plugin\Backend;

use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderAfterSave.
 */
class OrderAfterSave
{
    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * AfterPlaceOrder constructor.
     */
    public function __construct(
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->config = $config;
    }

    /**
     * Disable order email sending on order creation
     */
    public function afterSave(OrderRepositoryInterface $orderRepo, $order)
    {
        // Get the webhook entities
        $entities = $this->webhookHandler->loadEntities([
            'order_id' => $order->getId()
        ]);


        

        return $order;
    }
}