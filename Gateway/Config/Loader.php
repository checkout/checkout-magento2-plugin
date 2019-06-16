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
    protected $moduleDirReader;

    /**
     * @var Parser
     */
    protected $xmlParser;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Array
     */
    protected $xmlData;

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

        $this->data = $this->loadConfig();
    }

    protected function loadConfig()
    {
        try {
            // Prepare the output array
            $output = [];

            // Load the xml data
            $this->xmlData = $this->loadXmlData();

            // Build the config data array
            foreach ($this->xmlData['config'] as $parent => $child) {
                foreach ($child as $group => $arr) {
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

    protected function getFilePath($fileName)
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

    protected function loadXmlData()
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

    protected function isHidden($field)
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

    protected function isEncrypted($field)
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
