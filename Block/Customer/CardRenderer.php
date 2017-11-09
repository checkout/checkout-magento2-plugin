<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Block\Customer;

use Magento\Payment\Model\CcConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;

class CardRenderer extends AbstractCardRenderer {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * CardRenderer constructor.
     * @param Template\Context $context
     * @param CcConfigProvider $iconsProvider
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CcConfigProvider $iconsProvider,
        GatewayConfig $gatewayConfig,
        array $data
    ) {
        parent::__construct($context, $iconsProvider, $data);

        $this->gatewayConfig = $gatewayConfig;
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
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
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
