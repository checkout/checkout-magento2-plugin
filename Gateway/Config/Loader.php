<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

class Loader
{

    const CONFIGURATION_FILE_NAME = 'config.xml';

    protected $moduleDirReader;
    protected $xmlParser;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;

        $this->data = $this->getConfigFileData();
    }

    private function getConfigFileData() {
        return $this->xmlParser
        ->load($this->getConfigFilePath())
        ->xmlToArray()['config']['_value']['default'];
    }

    private function getConfigFilePath() {
        return $this->moduleDirReader->getModuleDir(
            Dir::MODULE_ETC_DIR,
            'CheckoutCom_Magento2'
        ) . '/' . self::CONFIGURATION_FILE_NAME;
    }
}
