<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Observer\Backend;

use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigService;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class HandleServiceKeyChange
 */
class AddBearerWhenNas implements ObserverInterface
{
    /**
     * Bearer key
     *
     * @var string BEARER_KEY
     */
    private const BEARER_KEY = 'Bearer ';
    /**
     * Service config path
     *
     * @var string SERVICE_CONFIG_PATH
     */
    private const SERVICE_CONFIG_PATH = 'settings/checkoutcom_configuration/service';
    /**
     * Secret key config path
     *
     * @var string SECRET_KEY_CONFIG_PATH
     */
    private const SECRET_KEY_CONFIG_PATH = 'settings/checkoutcom_configuration/secret_key';
    /**
     * Public key config path
     *
     * @var string PUBLIC_KEY_CONFIG_PATH
     */
    private const PUBLIC_KEY_CONFIG_PATH = 'settings/checkoutcom_configuration/public_key';
    /**
     * $config field
     *
     * @var ScopeConfigInterface $config
     */
    private $config;
    /**
     * $writer field
     *
     * @var WriterInterface $writer
     */
    private $writer;
    /**
     * $cacheTypeList field
     *
     * @var TypeListInterface $cacheTypeList
     */
    private $cacheTypeList;
    /**
     * $encryptor field
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;

    /**
     * @param EncryptorInterface   $encryptor
     * @param TypeListInterface    $cacheTypeList
     * @param WriterInterface      $writer
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        EncryptorInterface $encryptor,
        TypeListInterface $cacheTypeList,
        WriterInterface $writer,
        ScopeConfigInterface $config
    ) {
        $this->encryptor     = $encryptor;
        $this->cacheTypeList = $cacheTypeList;
        $this->config        = $config;
        $this->writer        = $writer;
    }

    /**
     * Add/Remove 'Bearer' from api keys based on service configuration
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        /** @var string $serviceConfig */
        $serviceConfig = $this->config->getValue(self::SERVICE_CONFIG_PATH);
        /** @var string $secretKey */
        $secretKey = $this->config->getValue(self::SECRET_KEY_CONFIG_PATH);
        /** @var string $publicKey */
        $publicKey = $this->config->getValue(self::PUBLIC_KEY_CONFIG_PATH);
        /** @var bool $keyChanged */
        $keyChanged = false;
        if ($serviceConfig === ConfigService::SERVICE_NAS) {
            if (strpos($secretKey, self::BEARER_KEY) === false) {
                $secretKey    = self::BEARER_KEY . $secretKey;
                $encryptedKey = $this->encryptor->encrypt($secretKey);
                $this->writer->save(self::SECRET_KEY_CONFIG_PATH, $encryptedKey);
                $keyChanged = true;
            }
            if (strpos($publicKey, self::BEARER_KEY) === false) {
                $publicKey = self::BEARER_KEY . $publicKey;
                $this->writer->save(self::PUBLIC_KEY_CONFIG_PATH, $publicKey);
                $keyChanged = true;
            }
            if ($keyChanged) {
                $this->cacheTypeList->cleanType('config');
            }
        }
    }
}
