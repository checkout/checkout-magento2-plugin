<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

class Loader
{

    const CONFIGURATION_FILE_NAME = 'config.xml';
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';
    const KEY_MODULE_ID = 'checkoutcom_magento2';
    const KEY_CONFIG = 'checkoutcom_configuration';
    const KEY_PAYMENT = 'payment';
    const KEY_SETTINGS = 'settings';

    /**
     * @var Parser
     */
    protected $moduleDirReader;

    /**
     * @var Parser
     */
    protected $xmlParser;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
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
        // Prepare the output array
        $dbData = [];

        // Load the xml data
        $xmlData = $this->loadXmlData();
  
        // Loop through the xml data array
        foreach ($xmlData as $parent => $child) {
            foreach ($child as $group => $arr) {
                foreach ($arr as $key => $val) {
                    if (!$this->isHidden($key)) {
                        $path = $parent . '/' . $group . '/' . $key;
                        $dbData[$parent][$group][$key] = ($this->isEncrypted($key))
                        ? $this->decrypt($path) 
                        : $this->getValue($path);
                    }
                }
            }
        }

        return $dbData;
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

    private function getValue($path) {
        $field = 'private_key';
        $value = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $value;
    }

    private function isHidden($field) {
        $hiddenFields = explode(',',
            $this->getValue('settings/checkoutcom_configuration/fields_hidden')
        );

        return in_array($field, $hiddenFields);
    }

    private function isEncrypted($field) {
        $encryptedFields = explode(',',
            $this->getValue('settings/checkoutcom_configuration/fields_encrypted')
        );

        return in_array($field, $encryptedFields);
    }

    private function decrypt($path) {
        $value = $this->getValue($path);
        return $this->encryptor->decrypt($value);
    }
}
