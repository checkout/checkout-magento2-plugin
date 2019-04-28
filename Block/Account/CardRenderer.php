<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Block\Account;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class CardRenderer extends \Magento\Vault\Block\AbstractCardRenderer {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * CardRenderer constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template $context,
        \Magento\Payment\Model\CcConfigProvider $iconsProvider,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        array $data
    ) {
        parent::__construct($context, $iconsProvider, $data);

        $this->config = $config;
    }

    /**
     * Returns 4 last digits from the credit card number.
     *
     * @return string
     */
    public function getNumberLast4Digits() {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * Returns the credit card expiration date.
     *
     * @return string
     */
    public function getExpDate() {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * Returns the url to the CC icon.
     *
     * @return string
     */
    public function getIconUrl() {
        return $this->getIconForType($this->getCartType())['url'];
    }

    /**
     * Returns the icon height in pixels.
     *
     * @return int
     */
    public function getIconHeight() {
        return $this->getIconForType($this->getCartType())['height'];
    }

    /**
     * Returns the icon width in pixels.
     *
     * @return int
     */
    public function getIconWidth() {
        return $this->getIconForType($this->getCartType())['width'];
    }

    /**
     * Determines if can render the given token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token) {
        return $token->getPaymentMethodCode() === 'checkoutcom_card_payment';
    }

    /**
     * Returns the credit card type.
     *
     * @return string
     */
    private function getCartType() {
        return $this->getTokenDetails()['type'];
    }
}
