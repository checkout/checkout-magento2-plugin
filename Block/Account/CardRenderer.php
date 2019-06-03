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
     * @var Config
     */
    protected $config;

    /**
     * @var CardHandlerService
     */
    public $cardHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * CardRenderer constructor.
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\CcConfigProvider $iconsProvider,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\CardHandlerService $cardHandler,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        array $data
    ) {
        parent::__construct($context, $iconsProvider, $data);

        $this->config = $config;
        $this->cardHandler = $cardHandler;
        $this->logger = $logger;
    }

    /**
     * Returns 4 last digits from the credit card number.
     *
     * @return string
     */
    public function getNumberLast4Digits() {
        try {
            return $this->getTokenDetails()['maskedCC'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Returns the credit card expiration date.
     *
     * @return string
     */
    public function getExpDate() {
        try {
            return $this->getTokenDetails()['expirationDate'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Determines if can render the given token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token) {
        try {
            return $token->getPaymentMethodCode() === 'checkoutcom_vault';
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Returns the credit card type.
     *
     * @return string
     */
    public function getCardType() {
        try {
            return $this->getTokenDetails()['type'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Returns the url to the CC icon.
     *
     * @return string
     */
    public function getIconUrl() {
        try {
            return $this->getIconForType($this->getCardType())['url'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Returns the icon height in pixels.
     *
     * @return int
     */
    public function getIconHeight() {
        try {
            return $this->getIconForType($this->getCardType())['height'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Returns the icon width in pixels.
     *
     * @return int
     */
    public function getIconWidth() {
        try {
            return $this->getIconForType($this->getCardType())['width'];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
