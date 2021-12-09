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

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use Magento\Store\Model\StoreManagerInterface;

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
    public $moduleDirReader;
    /**
     * $xmlParser field
     *
     * @var Parser $xmlParser
     */
    public $xmlParser;
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    public $scopeConfig;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    public $storeManager;
    /**
     * $encryptor field
     *
     * @var EncryptorInterface $encryptor
     */
    public $encryptor;
    /**
     * $xmlData field
     *
     * @var array $xmlData
     */
    public $xmlData;

    /**
     * Loader constructor
     *
     * @param Reader                $moduleDirReader
     * @param Parser                $xmlParser
     * @param ScopeConfigInterface   $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface    $encryptor
     */
    public function __construct(
        Reader $moduleDirReader,
        Parser $xmlParser,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser       = $xmlParser;
        $this->scopeConfig      = $scopeConfig;
        $this->storeManager    = $storeManager;
        $this->encryptor       = $encryptor;
    }

    /**
     * Prepares the loader class instance.
     *
     * @return Loader
     */
    public function init()
    {
        $this->data = $this->loadConfig();

        return $this;
    }

    /**
     * Loads the module configuration values.
     *
     * @return array
     */
    public function loadConfig()
    {
        // Prepare the output array
        $output = [];

        // Load the xml data
        $this->xmlData = $this->loadXmlData();

        // Build the config data array
        foreach ($this->xmlData['config'] as $parent => $child) {
            foreach ($child as $group => $arr) {
                $output = $this->processGroupValues($output, $arr, $parent, $group);
            }
        }

        // Load the APM list
        $output['settings']['checkoutcom_configuration']['apm_list'] = $this->loadApmList();

        return $output;
    }

    /**
     * Builds the config data array for an XML section
     *
     * @param $output
     * @param $arr
     * @param $parent
     * @param $group
     *
     * @return array
     */
    public function processGroupValues($output, $arr, $parent, $group)
    {
        // Loop through values for the payment method
        foreach ($arr as $key => $val) {
            if (!$this->isHidden($key)) {
                // Get the field  value in db
                $path  = $parent . '/' . $group . '/' . $key;
                $value = $this->scopeConfig->getValue(
                    $path,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );

                // Process encrypted fields
                if ($this->isEncrypted($key)) {
                    $value = $this->encryptor->decrypt($value);
                }

                // Add the final value to the config array
                $output[$parent][$group][$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Load the list of Alternative Payments.
     *
     * @return array
     */
    public function loadApmList()
    {
        // Build the APM array
        $output = [];
        foreach ($this->xmlData['apm'] as $row) {
            $output[] = [
                'value'      => $row['id'],
                'label'      => $row['title'],
                'currencies' => $row['currencies'],
                'countries'  => $row['countries'],
                'mappings'   => isset($row['mappings']) ? $row['mappings'] : '',
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
    public function getFilePath($fileName)
    {
        return $this->moduleDirReader->getModuleDir(
                Dir::MODULE_ETC_DIR,
                self::KEY_MODULE_NAME
            ) . '/' . $fileName;
    }

    /**
     * Reads the XML config files.
     *
     * @return array
     */
    public function loadXmlData()
    {
        // Prepare the output array
        $output = [];

        // Load config.xml
        $output['config'] = $this->xmlParser->load($this->getFilePath(self::CONFIGURATION_FILE_NAME))->xmlToArray(
        )['config']['_value']['default'];

        // Load apm.xml
        $output['apm'] = $this->xmlParser->load($this->getFilePath(self::APM_FILE_NAME))->xmlToArray(
        )['config']['_value']['item'];

        return $output;
    }

    /**
     * Checks if a filed value should be hidden in front end.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function isHidden($field)
    {
        $configHiddenFields = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/fields_hidden',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        // Apple pay configuration
        $applePayHiddenFields = explode(
            ',',
            $this->scopeConfig->getValue(
                'payment/checkoutcom_apple_pay/fields_hidden',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        return in_array($field, array_merge($configHiddenFields, $applePayHiddenFields));
    }

    /**
     * Checks if a field value is encrypted.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function isEncrypted($field)
    {
        $encryptedFields = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/fields_encrypted',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        return in_array($field, $encryptedFields);
    }

    /**
     * Get a field value
     *
     * @param        $key
     * @param null   $methodId
     * @param null   $storeCode
     * @param string $scope
     *
     * @return mixed|string
     */
    public function getValue(
        $key,
        $methodId = null,
        $storeCode = null,
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
