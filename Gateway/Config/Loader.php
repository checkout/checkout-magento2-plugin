<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

class Loader
{

    const CONFIGURATION_FILE_NAME = 'config.xml';
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';

    protected $moduleDirReader;
    protected $xmlParser;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $this->data = $this->getConfigFileData();
    }

    private function getConfigFileData() {
        // Prepare the output array
        $dbData = [];

        // Load the xml data
        $xmlData = $this->xmlParser
        ->load($this->getConfigFilePath())
        ->xmlToArray()['config']['_value']['default'];
  
        // Loop through the xml data array
        foreach ($xmlData as $parent => $child) {
            foreach ($child as $group => $arr) {
                foreach ($arr as $key => $val) {
                    $path = $parent . '/' . $group . '/' . $key;
                    $dbData[$parent][$group][$key] = $this->scopeConfig->getValue(
                        $path,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
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
}
