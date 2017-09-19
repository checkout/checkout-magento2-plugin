<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Form\Cc;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Helper\Data;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class Form extends Cc {

    /**
     * Payment config model
     *
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var Data
     */
    private $paymentDataHelper;

    /**
     * Form constructor.
     * @param Context $context
     * @param Config $paymentConfig
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(Context $context, Config $paymentConfig, GatewayConfig $gatewayConfig, array $data) {
        parent::__construct($context, $paymentConfig, $data);

        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Determines if the gateway needs CVV.
     *
     * @return bool
     */
    public function useCvv() {
        return $this->gatewayConfig->isCvvEnabled();
    }

    /**
     * Determines if the gateway supports CC store for later use.
     *
     * @return bool
     */
    public function isVaultEnabled() {
        $storeId        = $this->_storeManager->getStore()->getId();
        $vaultPayment   = $this->getVaultPayment();

        return $vaultPayment->isActive($storeId);
    }

    /**
     * Returns the Vault Payment instance.
     *
     * @return VaultPaymentInterface
     * @throws LocalizedException
     */
    private function getVaultPayment() {
        return $this->getPaymentDataHelper()->getMethodInstance(ConfigProvider::CC_VAULT_CODE);
    }

    /**
     * Returns payment data helper instance.
     *
     * @return Data
     * @throws \RuntimeException
     */
    private function getPaymentDataHelper() {
        if ($this->paymentDataHelper === null) {
            $this->paymentDataHelper = ObjectManager::getInstance()->get(Data::class);
        }
        return $this->paymentDataHelper;
    }

}
