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

/**
 * Class OrderAfterSave.
 */
class OrderAfterSave
{
    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepositoryInterface;

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
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->webhookHandler = $webhookHandler;
        $this->config = $config;
    }

    /**
     * Disable order email sending on order creation
     */
    public function afterSave($order)
    {

        $entities = $this->webhookHandler->loadEntities();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/w.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($entities, 1));
        

        return $order;
    }
}