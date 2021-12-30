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

namespace CheckoutCom\Magento2\Plugin;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Class AfterPlaceOrder
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class AfterPlaceOrder
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $webhookHandler field
     *
     * @var WebhookHandlerService $webhookHandler
     */
    public $webhookHandler;

    /**
     * AfterPlaceOrder constructor
     *
     * @param Session               $backendAuthSession
     * @param Config                $config
     * @param WebhookHandlerService $webhookHandler
     */
    public function __construct(
        Session $backendAuthSession,
        Config $config,
        WebhookHandlerService $webhookHandler
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->config             = $config;
        $this->webhookHandler     = $webhookHandler;
    }

    /**
     * Description afterPlace function
     *
     * @param OrderManagementInterface $subject
     * @param OrderInterface           $order
     *
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function afterPlace(OrderManagementInterface $subject, OrderInterface $order)
    {
        // Get the method ID
        $methodId = $order->getPayment()->getMethodInstance()->getCode();

        // If can proceed
        if (in_array($methodId, $this->config->getMethodsList())) {
            // Disable the email sending
            $order->setCanSendNewEmailFlag(false);
        }

        return $order;
    }
}
