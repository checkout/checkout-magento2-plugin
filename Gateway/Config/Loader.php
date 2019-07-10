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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

/**
 * Class Loader
 */
class Loader
{
    const CONFIGURATION_FILE_NAME = 'config.xml';
    const APM_FILE_NAME = 'apm.xml';
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';
    const KEY_MODULE_ID = 'checkoutcom_magento2';
    const KEY_PAYMENT = 'payment';
    const KEY_SETTINGS = 'settings';
    const KEY_CONFIG = 'checkoutcom_configuration';

    /**
     * @var Dir
     */
    public $moduleDirReader;

    /**
     * @var Parser
     */
    public $xmlParser;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var EncryptorInterface
     */
    public $encryptor;

    /**
     * @var Reader
     */
    public $directoryReader;

    /**
     * @var Array
     */
    public $xmlData;

    /**
     * Loader constructor
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\Dir\Reader $directoryReader
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->directoryReader = $directoryReader;
    }

    /**
     * Prepares the loader class instance.
     *
     * @return void
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
        try {
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
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Builds the config data array for an XML section.
     *
     * @return void
     */
    public function processGroupValues($output, $arr, $parent, $group)
    {
        // Loop through values for the payment method
        foreach ($arr as $key => $val) {
            if (!$this->isHidden($key)) {
                // Get the field  value in db
                $path = $parent . '/' . $group . '/' . $key;
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

        try {
            foreach ($this->xmlData['apm'] as $row) {
                $output[] = [
                    'value' => $row['id'],
                    'label' => $row['title'],
                    'currencies' => $row['currencies']
                ];
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        } finally {
            return $output;
        }
    }

    /**
     * Finds a file path from file name.
     *
     * @param string $fileName
     * @return string
     */
    public function getFilePath($fileName)
    {
        try {
            return $this->moduleDirReader->getModuleDir(
                Dir::MODULE_ETC_DIR,
                self::KEY_MODULE_NAME
            ) . '/' . $fileName;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
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

        try {
            // Load config.xml
            $output['config'] = $this->xmlParser
                ->load($this->getFilePath(self::CONFIGURATION_FILE_NAME))
                ->xmlToArray()['config']['_value']['default'];

            // Load apm.xml
            $output['apm'] = $this->xmlParser
                ->load($this->getFilePath(self::APM_FILE_NAME))
                ->xmlToArray()['config']['_value']['item'];
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        } finally {
            return $output;
        }
    }

    /**
     * Checks if a filed value should be hidden in front end.
     *
     * @param string $field
     * @return boolean
     */
    public function isHidden($field)
    {
        try {
            $hiddenFields = explode(
                ',',
                $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/fields_hidden',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            );

            return in_array($field, $hiddenFields);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Checks if a field value is encrypted.
     *
     * @param string $field
     * @return boolean
     */
    public function isEncrypted($field)
    {
        try {
            return in_array(
                $field,
                explode(
                    ',',
                    $this->scopeConfig->getValue(
                        'settings/checkoutcom_configuration/fields_encrypted',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Get a field value.
     *
     * @param string $key
     * @param string $methodId
     * @return string
     */
    public function getValue($key, $methodId = null)
    {
        try {
            // Prepare the path
            $path = ($methodId)
            ? 'payment/' . $methodId  . '/' .  $key
            : 'settings/checkoutcom_configuration/' . $key;

            // Get field value in database
            $value = $this->scopeConfig->getValue(
                $path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // Return a decrypted value for encrypted fields
            if ($this->isEncrypted($key)) {
                return $this->encryptor->decrypt($value);
            }

            // Return a normal value
            return $value;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
