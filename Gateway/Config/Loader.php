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

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Loader
 */
class Loader
{
    /**
     * CONFIGURATION_FILE_NAME constant
     *
     * @var string CONFIGURATION_FILE_NAME
     */
    const CONFIGURATION_FILE_NAME = 'config.xml';
    /**
     * APM_FILE_NAME constant
     *
     * @var string APM_FILE_NAME
     */
    const APM_FILE_NAME = 'apm.xml';
    /**
     * KEY_MODULE_NAME constant
     *
     * @var string KEY_MODULE_NAME
     */
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';
    /**
     * KEY_MODULE_ID constant
     *
     * @var string KEY_MODULE_ID
     */
    const KEY_MODULE_ID = 'checkoutcom_magento2';
    /**
     * KEY_PAYMENT constant
     *
     * @var string KEY_PAYMENT
     */
    const KEY_PAYMENT = 'payment';
    /**
     * KEY_SETTINGS constant
     *
     * @var string KEY_SETTINGS
     */
    const KEY_SETTINGS = 'settings';
    /**
     * KEY_CONFIG constant
     *
     * @var string KEY_CONFIG
     */
    const KEY_CONFIG = 'checkoutcom_configuration';
    /**
     * $moduleDirReader field
     *
     * @var Dir $moduleDirReader
     */
    private $moduleDirReader;
    /**
     * $xmlParser field
     *
     * @var Parser $xmlParser
     */
    private $xmlParser;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $encryptor field
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;

    /**
     * Loader constructor
     *
     * @param Reader                $moduleDirReader
     * @param Parser                $xmlParser
     * @param ScopeConfigInterface  $scopeConfig
     * @param EncryptorInterface    $encryptor
     */
    public function __construct(
        Reader $moduleDirReader,
        Parser $xmlParser,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser       = $xmlParser;
        $this->scopeConfig     = $scopeConfig;
        $this->encryptor       = $encryptor;
    }

    /**
     * Load the list of Alternative Payments.
     *
     * @return string[][]
     */
    public function loadApmList(): array
    {
        /** @var array $apmXmlData */
        $apmXmlData = $this->loadApmXmlData();

        // Build the APM array
        /** @var array $output */
        $output = [];
        /** @var mixed[] $row */
        foreach ($apmXmlData as $row) {
            $output[] = [
                'value'      => $row['id'],
                'label'      => $row['title'],
                'currencies' => $row['currencies'],
                'countries'  => $row['countries'],
                'mappings'   => $row['mappings'] ?? '',
            ];
        }

        return $output;
    }

    /**
     * Finds a file path from file name.
     *
     * @param string $fileName
     *
     * @return string
     */
    public function getFilePath(string $fileName): string
    {
        return $this->moduleDirReader->getModuleDir(
                Dir::MODULE_ETC_DIR,
                self::KEY_MODULE_NAME
            ) . '/' . $fileName;
    }

    /**
     * Load the apm.xml data
     *
     * @return string[][]
     */
    public function loadApmXmlData(): array
    {
        return $this->xmlParser->load($this->getFilePath(self::APM_FILE_NAME))->xmlToArray(
        )['config']['_value']['item'];
    }

    /**
     * Checks if a filed value should be hidden in front end.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function isHidden(string $field): bool
    {
        $configHiddenFields = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/fields_hidden',
            ScopeInterface::SCOPE_STORE
        );
        if ($configHiddenFields) {
            $configHiddenFields = explode(
                ',',
                $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/fields_hidden',
                    ScopeInterface::SCOPE_STORE
                )
            );

            return in_array($field, $configHiddenFields);
        }

        // Apple pay configuration
        $applePayHiddenFields = $this->scopeConfig->getValue(
            'payment/checkoutcom_apple_pay/fields_hidden',
            ScopeInterface::SCOPE_STORE
        );
        if ($applePayHiddenFields) {
            $applePayHiddenFields = explode(
                ',',
                $this->scopeConfig->getValue(
                    'payment/checkoutcom_apple_pay/fields_hidden',
                    ScopeInterface::SCOPE_STORE
                )
            );

            return in_array($field, $applePayHiddenFields);
        }

        return false;
    }

    /**
     * Checks if a field value is encrypted.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function isEncrypted(string $field): bool
    {
        $encryptedFields = $this->scopeConfig->getValue(
            'settings/checkoutcom_configuration/fields_encrypted',
            ScopeInterface::SCOPE_STORE
        );

        if ($encryptedFields) {
            $encryptedFields = explode(
                ',',
                $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/fields_encrypted',
                    ScopeInterface::SCOPE_STORE
                )
            );
            return in_array($field, $encryptedFields);
        }

        return false;
    }

    /**
     * Get a field value
     *
     * @param string          $key
     * @param string|null     $methodId
     * @param string|int|null $storeCode
     * @param string|null     $scope
     *
     * @return mixed|string
     */
    public function getValue(
        string $key,
        string $methodId = null,
        $storeCode = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ) {
        // Prepare the path
        $path = ($methodId) ? 'payment/' . $methodId . '/' . $key : 'settings/checkoutcom_configuration/' . $key;

        // Get field value in database
        $value = $this->scopeConfig->getValue(
            $path,
            $scope,
            $storeCode
        );

        // Return a decrypted value for encrypted fields
        if ($this->isEncrypted($key)) {
            return $this->encryptor->decrypt($value);
        }

        // Return a normal value
        return $value;
    }
}
