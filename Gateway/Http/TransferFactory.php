<?php

namespace CheckoutCom\Magento2\Gateway\Http;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var array
     */
    private static $headers = [
        'Content-Type'  => 'application/json;charset=UTF-8',
        'Accept'        => 'application/json',
    ];

    /**
     * @var array
     */
    private static $clientConfig = [
        'timeout' => 60,
    ];

    /**
     * TransferFactory constructor.
     * @param Config $config
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(Config $config, TransferBuilder $transferBuilder) {
        $this->config           = $config;
        $this->transferBuilder  = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request) {
        $headers = self::$headers;

        $headers['Authorization'] = $this->config->getSecretKey();

        return $this->transferBuilder
            ->setClientConfig(self::$clientConfig)
            ->setHeaders($headers)
            ->setUri($this->config->getApiUrl())
            ->setBody($request)
            ->build();
    }

}
