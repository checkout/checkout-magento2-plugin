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

namespace CheckoutCom\Magento2\Plugin\Backend;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderAfterSave
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class OrderAfterSave
{
    /**
     * $backendAuthSession field
     *
     * @var Session $backendAuthSession
     */
    public $backendAuthSession;
    /**
     * $webhookHandler field
     *
     * @var WebhookHandlerService $webhookHandler
     */
    public $webhookHandler;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $request field
     *
     * @var RequestInterface $request
     */
    public $request;

    /**
     * OrderAfterSave constructor
     *
     * @param Session               $backendAuthSession
     * @param WebhookHandlerService $webhookHandler
     * @param Config                $config
     * @param RequestInterface      $request
     */
    public function __construct(
        Session $backendAuthSession,
        WebhookHandlerService $webhookHandler,
        Config $config,
        RequestInterface $request
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->webhookHandler     = $webhookHandler;
        $this->config             = $config;
        $this->request            = $request;
    }

    /**
     * Create transactions for the order.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param                          $order
     *
     * @return mixed
     * @throws Exception
     */
    public function afterSave(OrderRepositoryInterface $orderRepository, $order)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the method ID
            $methodId = $order->getPayment()->getMethodInstance()->getCode();

            // Process the webhooks if order is not on hold
            if (in_array($methodId, $this->config->getMethodsList()) && $this->needsWebhookProcessing()) {
                $this->webhookHandler->processAllWebhooks($order);
            }
        }

        return $order;
    }

    /**
     * Don't process the stored webhooks after admin refund.
     *
     * @return bool
     */
    public function needsWebhookProcessing()
    {
        $params = $this->request->getParams();

        return isset($params['creditmemo']) ? false : true;
    }
}
