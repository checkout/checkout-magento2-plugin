<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

class Loader
{

    const CONFIGURATION_FILE_NAME = 'config.xml';
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';
    const KEY_MODULE_ID = 'checkoutcom_magento2';
    const KEY_PAYMENT = 'payment';
    const KEY_SETTINGS = 'settings';

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
     * Loader constructor
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;

        $this->data = $this->getConfigFileData();
    }

    private function getConfigFileData() {
        try {
            // Prepare the output array
            $dbData = [];

            // Load the xml data
            $xmlData = $this->loadXmlData();
    
            // Loop through the xml data array
            foreach ($xmlData as $parent => $child) {
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
                            $dbData[$parent][$group][$key] = $value;
                        }
                    }
                }
            }

            return $dbData;
        }
        catch(\Exception $e) {
            throw new \Magento\Framework\Exception\Exception(
                __(
                    "The module configuration file can't be loaded" . " - "
                    . $e->getMessage()
                )
            );          
        }
    }

    private function getConfigFilePath() {
        return $this->moduleDirReader->getModuleDir(
            Dir::MODULE_ETC_DIR,
            self::KEY_MODULE_NAME
        ) . '/' . self::CONFIGURATION_FILE_NAME;
    }

    private function loadXmlData() {
        return $this->xmlParser
        ->load($this->getConfigFilePath())
        ->xmlToArray()['config']['_value']['default'];
    }

    private function isHidden($field) {
        $hiddenFields = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/fields_hidden',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        return in_array($field, $hiddenFields);
    }

    private function isEncrypted($field) {
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
    }

    public function getValue($key, $methodId = null) {
        // Prepare the path
        $path = ($methodId) 
        ? 'payment/' . $methodId  . '/' .  $key
        : 'settings/checkoutcom_configuration/' . $key;

        // Return a decrypted value for encrypted fields
        if ($this->isEncrypted($key)) {
            return $this->encryptor->decrypt($key);
        }

        // Return a normal value
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
