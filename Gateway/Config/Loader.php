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
     * @var Csv
     */
    protected $csvParser;

    /**
     * Loader constructor
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\Dir\Reader $directoryReader,
        \Magento\Framework\File\Csv $csvParser
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    	$this->directoryReader = $directoryReader;
    	$this->csvParser = $csvParser;

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

            // Load the APM list
            $dbData['settings']['checkoutcom_configuration']['apm_list'] = $this->loadApmList();

            return $dbData;
        }
        catch(\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    "The module configuration file can't be loaded" . " - "
                    . $e->getMessage()
                )
            );          
        }
    }

    private function loadApmList() {
        // Get the CSV path
        $csvPath = $this->directoryReader->getModuleDir('', 
        'CheckoutCom_Magento2') . '/' . $this->getValue('apm_file');

        // Load the data
        $csvData = $this->csvParser->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the APM array
        $apmArray = [];
        foreach ($csvData as $row) {
            $apmArray[] = [
                'value' => $row[0],
                'label' => __($row[1])
            ];
        }

        return $apmArray;
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
    }
}
