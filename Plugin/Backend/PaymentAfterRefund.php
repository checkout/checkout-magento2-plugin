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
 * Class PaymentAfterRefund.
 */
class PaymentAfterRefund
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var WebhookHandlerService
     */
    public $webhookHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * PaymentAfterRefund constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\WebhookHandlerService $webhookHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->webhookHandler = $webhookHandler;
        $this->config = $config;
    }

    /**
     * Create transactions for the order.
     */
    public function afterRefund(\Magento\Payment\Model\Method\AbstractMethod $method, $amount)
    {
        if ($this->backendAuthSession->isLoggedIn()) { 
            // Get the payment
            $payment = $method->getInfoInstance();

            // Get the method id
            $methodId = $payment->getMethodInstance()->getCode();

            // Process the webhooks
            if (in_array($methodId, $this->config->getMethodsList())) {
                // Get the order
                $order = $payment->getOrder();

                // Process the webhooks
                $this->webhookHandler->processAllWebhooks($order);
            }
        }

        return $method;
    }
}