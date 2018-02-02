<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Ui\Adminhtml;

use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface {

    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * TokenUiComponentProvider constructor.
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory, UrlInterface $urlBuilder) {
        $this->componentFactory = $componentFactory;
        $this->urlBuilder       = $urlBuilder;
    }
    /**
     * @inheritdoc
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken) {
        $data = json_decode($paymentToken->getTokenDetails() ?: '{}', true);

        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => ConfigProvider::CC_VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $data,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'CheckoutCom_Magento2::form/vault.phtml'
                ],
                'name' => Template::class
            ]
        );

        return $component;
    }

}
