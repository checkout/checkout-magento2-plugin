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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class VersionHandlerService.
 */
class VersionHandlerService
{
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;
    /**
     * $curl field
     *
     * @var Curl $curl
     */
    public $curl;
    /**
     * $moduleDirReader field
     *
     * @var ModuleDirReader $moduleDirReader
     */
    public $moduleDirReader;
    /**
     * $storeManager field
     *
     * @var $storeManager $storeManager
     */
    public $storeManager;
    /**
     * $fileDriver field
     *
     * @var File $fileDriver
     */
    private $fileDriver;

    /**
     * VersionHandlerService constructor
     *
     * @param Config                 $config
     * @param Curl                  $curl
     * @param Reader                $moduleDirReader
     * @param File                  $fileDriver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        Curl $curl,
        Reader $moduleDirReader,
        File $fileDriver,
        StoreManagerInterface $storeManager
    ) {
        $this->config           = $config;
        $this->curl            = $curl;
        $this->moduleDirReader = $moduleDirReader;
        $this->fileDriver       = $fileDriver;
        $this->storeManager    = $storeManager;
    }

    /**
     * Returns type of version update
     *
     * @param $currentVersion
     * @param $latestVersion
     *
     * @return string|void
     */
    public function getVersionType($currentVersion, $latestVersion)
    {
        $versions = [explode('.', $currentVersion), explode('.', $latestVersion)];

        // Compare version numbers - major, minor and then revision
        for ($i = 0; $i < 3; $i++) {
            if ($versions[0][$i] < $versions[1][$i]) {
                switch ($i) {
                    case 0:
                        return 'major';
                    case 1:
                        return 'minor';
                    case 2:
                        return 'revision';
                }
            }
        }
    }

    /**
     * Check if module needs updating
     *
     * @param $currentVersion
     * @param $latestVersion
     *
     * @return bool
     */
    public function needsUpdate($currentVersion, $latestVersion)
    {
        $versions = [explode('.', $currentVersion), explode('.', $latestVersion)];

        // Compare version numbers - major, minor and then revision
        for ($i = 0; $i < 3; $i++) {
            if ($versions[0][$i] < $versions[1][$i]) {
                return true;
            };
        }

        return false;
    }

    /**
     * Get array of releases from Github
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getVersions()
    {
        $this->curl->setHeaders([
            'User-Agent' => 'checkout-magento2-plugin',
        ]);

        $storeCode = $this->storeManager->getStore()->getCode();

        // Get Github API URL from config
        $gitApiUrl = $this->config->getValue('github_api_url', null, $storeCode);

        // Send the request
        $this->curl->get($gitApiUrl);

        // Get the response
        $content = $this->curl->getBody();

        // Return the array of releases
        return json_decode($content, true);
    }

    /**
     * Get latest version number
     *
     * @param $versions
     *
     * @return mixed|void
     */
    public function getLatestVersion($versions)
    {
        foreach ($versions as $version) {
            // Find latest release that is not beta
            if (isset($version['tag_name']) && count(explode('-', $version['tag_name'])) == 1) {
                return $version['tag_name'];
            }
        }
    }

    /**
     * Get the module version
     *
     * @param string $prefix
     *
     * @return string
     * @throws FileSystemException
     */
    public function getModuleVersion($prefix = '')
    {
        // Build the composer file path
        $filePath = $this->moduleDirReader->getModuleDir(
                '',
                'CheckoutCom_Magento2'
            ) . '/composer.json';

        // Get the composer file content
        $fileContent = json_decode(
            $this->fileDriver->fileGetContents($filePath)
        );

        return $prefix . $fileContent->version;
    }
}
