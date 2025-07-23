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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Plugin\Backend;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\WebhookHandlerService;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderAfterSave
 */
class OrderAfterSave
{
    public function __construct(
        private Session $backendAuthSession,
        private WebhookHandlerService $webhookHandler,
        private Config $config,
        private RequestInterface $request
    ) {
    }

    /**
     * Create transactions for the order.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface $order
     *
     * @return OrderInterface
     * @throws Exception
     */
    public function afterSave(OrderRepositoryInterface $orderRepository, OrderInterface $order): OrderInterface
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
    public function needsWebhookProcessing(): bool
    {
        $params = $this->request->getParams();

        return !isset($params['creditmemo']);
    }
}
