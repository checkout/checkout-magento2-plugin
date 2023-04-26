<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Plugin;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\CompositeConfigProvider;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AddConfigDataToCart
{
    protected Config $config;
    protected CompositeConfigProvider $compositeConfigProvider;

    public function __construct(
        Config $config,
        CompositeConfigProvider $compositeConfigProvider
    ) {
        $this->config = $config;
        $this->compositeConfigProvider = $compositeConfigProvider;
    }

    /**
     * @param Cart $subject
     * @param array $result
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        $configProvider = ['checkoutConfigProvider' => $this->compositeConfigProvider->getConfig()];

        return array_merge($this->config->getMethodsConfig(), $configProvider, $result);
    }
}
