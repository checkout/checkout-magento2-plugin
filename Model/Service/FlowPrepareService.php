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

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Model\Request\PostPaymentSessions;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class FlowPrepareService
 */
class FlowPrepareService
{
    protected PostPaymentSessions $postPaymentSession;

    public function __construct(
        PostPaymentSessions $postPaymentSession,
    ) {
        $this->postPaymentSession = $postPaymentSession;
    }

    public function prepare(CartInterface $quote, array $data) {
        
        $payload = $this->postPaymentSession->get($quote, $data);
        
        return null;
    }
}
