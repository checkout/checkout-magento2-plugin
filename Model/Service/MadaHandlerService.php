<?php

namespace CheckoutCom\Magento2\Model\Service;

class MadaHandlerService
{
    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $directoryReader,
        \Magento\Framework\File\Csv $csvParser,
    	\CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
    	$this->directoryReader = $directoryReader;
    	$this->csvParser = $csvParser;
        $this->config = $config;
    }

    /**
     * Returns the Mada CSV path
     *
     * @return string
     */
    private function getCsvPath() {
        $path = (($this->isLive()) ?
        $this->getValue(
            self::KEY_MADA_BINS_PATH,
            $this->storeManager->getStore()
        ) : 
        $this->getValue(
            self::KEY_MADA_BINS_PATH_TEST,
            $this->storeManager->getStore()
        ));

        return  $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2')
        . '/' . $this->getMadaFilePath();
    }

    /**
     * Checks the MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin) {
        // Set the root path
        $csvPath = $this->getCsvPath();

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