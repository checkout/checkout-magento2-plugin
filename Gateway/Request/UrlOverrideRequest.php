<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Request;

use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class UrlOverrideRequest extends AbstractRequest {

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(ConfigProvider $configProvider) {
        $this->configProvider  = $configProvider;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject) {

        $data = [
            'successUrl' => $this->configProvider->getSuccessUrl(),
            'failUrl'  => $this->configProvider->getFailUrl(),
        ];

        return $data;
    }

}