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

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

/**
 * Class WebhookUrl
 */
class WebhookUrl extends AbstractCallbackUrl
{
    /**
     * Returns the controller url.
     *
     * @return string
     */
    public function getControllerUrl(): string
    {
        return 'webhook/callback';
    }
}
