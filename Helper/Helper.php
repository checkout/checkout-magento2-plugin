<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\File\Csv;
use CheckoutCom\Magento2\Gateway\Config\Config;

class Helper {

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Csv
     */
    protected $csvParser;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Reader $directoryReader, Csv $csvParser, Config $config) {
        $this->directoryReader = $directoryReader;
        $this->csvParser = $csvParser;
        $this->config = $config;
    }

    /**
     * Get the module version from composer.json file
     */    
    public function getModuleVersion() {
        // Get the module path
        $module_path = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2');

        // Get the content of composer.json
        $json = file_get_contents($module_path . '/composer.json');

        // Decode the data and return
        $data = json_decode($json);

        return $data->version;
    }

    /**
     * Checks the MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin) {
        // Set the root path
        $csvPath = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2')  . '/' . $this->config->getMadaBinsPath();

        // Get the data
        $csvData = $this->csvParser->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the MADA BIN array
        $binArray = [];
        foreach ($csvData as $row) {
            $binArray[] = $row[1];
        }

        return in_array($bin, $binArray);
    }
}
