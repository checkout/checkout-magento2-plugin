<?php

namespace CheckoutCom\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;

class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'checkout_com';

    const CC_VAULT_CODE = 'checkout_com_cc_vault';

    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig() {
        return [
            'payment' => [
                self::CODE => [
                    'isActive'                  => $this->config->isActive(),
                    'debug_mode'                => $this->config->isDebugMode(),
                    'public_key'                => $this->config->getPublicKey(),
                    'sdk_url'                   => $this->config->getSdkUrl(),
                    'hosted_url'                => $this->config->getHostedUrl(),
                    'countrySpecificCardTypes'  => $this->config->getCountrySpecificCardTypeConfig(),
                    'availableCardTypes'        => $this->config->getAvailableCardTypes(),
                    'useCvv'                    => $this->config->isCvvEnabled(),
                    'ccTypesMapper'             => $this->config->getCcTypesMapper(),
                    'ccVaultCode'               => self::CC_VAULT_CODE,
                    Config::CODE_3DSECURE       => [
                        'enabled' => $this->config->isVerify3DSecure(),
                    ],
                    'integration'               => [
                        'type'          => $this->config->getIntegration(),
                        'isHosted'      => $this->config->isHostedIntegration(),
                        'isWidget'      => $this->config->isWidgetIntegration(),
                    ],
                    'priceAdapter' => ChargeAmountAdapter::getConfigArray(),
                ],
            ],
        ];
    }

}
