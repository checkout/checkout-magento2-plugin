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

namespace CheckoutCom\Magento2\Plugin;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Class AfterPlaceOrder
 */
class AfterPlaceOrder
{
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;

    /**
     * AfterPlaceOrder constructor
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
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
    public function afterPlace(OrderManagementInterface $subject, OrderInterface $order): OrderInterface
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
