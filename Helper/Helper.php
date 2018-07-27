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

class Helper {

    /**
     * @var Reader
     */
    protected $directoryReader;

    public function __construct(Reader $directoryReader) {
        $this->directoryReader = $directoryReader;
    }

    /**
     * Get the module version from composer.json file
     */    
    private function getModuleVersion() {
        // Get the module path
        $module_path = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2');

        // Get the content of composer.json
        $json = file_get_contents($module_path . '/composer.json');

        // Decode the data and return
        $data = json_decode($json);

        return $data->version;
    }
}
