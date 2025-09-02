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

namespace CheckoutCom\Magento2\Observer\Email;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Methods\PayByLinkMethod;
use Magento\Framework\Escaper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AddPaymentLinkToOrder
 */
class AddPaymentLinkToOrder implements ObserverInterface
{
    private Config $config;
    private Escaper $escaper;

    public function __construct(
        Config $config,
        Escaper $escaper
    ) {
        $this->config = $config;
        $this->escaper = $escaper;
    }

    /**
     * Add the pay by link link in the email
     */
    public function execute(Observer $observer): void
    {
        $transportObject = $observer->getEvent()->getData('transportObject');
        /** @var OrderInterface $order */
        $order = $transportObject->getOrder();
        if ($transportObject === null || $order === null || $order->getPayment() === null) {
            return;
        }
        $storeCode = $order->getStore()->getCode();
        $paymentLink = (string)$order->getPayment()->getAdditionalInformation(PayByLinkMethod::ADDITIONAL_INFORMATION_LINK_CODE);
        if (
            $order->getStatus() === $this->config->getValue('order_status_waiting_payment', PayByLinkMethod::CODE, $storeCode, ScopeInterface::SCOPE_STORE) &&
            $order->getPayment()->getMethod() === PayByLinkMethod::CODE &&
            $paymentLink
        ) {
            $transportObject->setData('payment_html', $transportObject->getData('payment_html') . $this->getPayByLinkHtml($paymentLink));
        }
    }

    public function getPayByLinkHtml(string $link): string
    {
        return '<p>
                    <i>
                        '.__('To validate your order, please click on the link below to be redirected to a secured payment page and choose your payment method:') . '
                    </i>
                </p>
                <a target="_blank" href="' . $this->escaper->escapeUrl($link) . '">
                    <button>' . __('Pay my order') . '</button>
                </a>';
    }
}
