<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;



/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'checkoutcom_gateway';

    protected $config;

    public function __construct(\CheckoutCom\Magento2\Gateway\Config\Config $config) {
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {


$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
$logger = new \Zend\Log\Logger();
$logger->addWriter($writer);
$logger->info('model' . $this->config->getTitle());



        return [
            'payment' => [
                self::CODE => [
                    'title' => $this->config->getTitle()
                ]
            ]
        ];
    }
}
