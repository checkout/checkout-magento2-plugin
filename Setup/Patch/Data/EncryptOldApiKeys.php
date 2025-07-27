<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class EncryptOldApiKeys
 * Runs a compatibility script to encrypt sensitive fields
 * if module was upgraded from anything before 3.0.0
 */
class EncryptOldApiKeys implements DataPatchInterface
{
    /**
     * CONFIG_PATHS const
     *
     * @var string[] CONFIG_PATH
     */
    const CONFIG_PATHS = [
        'settings/checkoutcom_configuration/secret_key',
        'settings/checkoutcom_configuration/private_shared_key',
        'payment/checkoutcom_moto/secret_key',
        'payment/checkoutcom_moto/private_shared_key',
    ];
    /**
     * $encryptor field
     *
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * $writer field
     *
     * @var WriterInterface
     */
    private $writer;
    /**
     * $moduleDataSetup field
     *
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * EncryptOldApiKeys constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EncryptorInterface $encryptor
     * @param WriterInterface $writer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EncryptorInterface $encryptor,
        WriterInterface $writer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->encryptor = $encryptor;
        $this->writer = $writer;
    }

    /**
     * Get patch dependencies
     *
     * @return string[]|void
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get patch aliases
     *
     * @return string[]|void
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Run patch
     *
     * @return EncryptOldApiKeys|void
     */
    public function apply()
    {
        /** @var AdapterInterface $setup */
        $setup = $this->moduleDataSetup->getConnection()->startSetup();

        /** @var string $configPath */
        foreach (self::CONFIG_PATHS as $configPath) {
            // Get fields directly form db to prevent magento from trying to decrypt unencrypted values
            /** @var string $coreConfigDataTable */
            $coreConfigDataTable = $setup->getTableName('core_config_data');
            /** @var Select $query */
            $query = $setup->select()->from($coreConfigDataTable, ['scope', 'scope_id', 'value'])->where('path = ?', $configPath);
            /** @var mixed[] $oldKeys */
            $oldKeys = $setup->fetchAll($query);

            /** @var mixed[] $oldKey */
            foreach ($oldKeys as $oldKey) {
                $this->encryptKey($oldKey, $configPath);
            }
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @param mixed[] $oldKey
     * @param string $configPath
     *
     * @return void
     */
    public function encryptKey(array $oldKey, string $configPath): void
    {
        if ($oldKey) {
            /** @var string $encryptedKey */
            $encryptedKey = $this->encryptor->encrypt($oldKey['value']);
            $this->writer->save($configPath, $encryptedKey, $oldKey['scope'], (int)$oldKey['scope_id']);
        }
    }
}
