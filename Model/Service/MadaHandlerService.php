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

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class MadaHandlerService.
 */
class MadaHandlerService
{

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

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $directoryReader,
        \Magento\Framework\File\Csv $csvParser,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->directoryReader = $directoryReader;
        $this->csvParser = $csvParser;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Returns the Mada CSV path
     *
     * @return string
     */
    private function getCsvPath()
    {
        try {
            // Get the MADA file name
            $file = ($this->config->isLive()) ? 'mada_live_file' : 'mada_test_file';

            // Get the MADA file path
            $path = $this->config->getValue($file);

            return $this->directoryReader->getModuleDir(
                '',
                'CheckoutCom_Magento2'
            ) . '/' . $path;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }

    /**
     * Checks the MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin)
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }
}
