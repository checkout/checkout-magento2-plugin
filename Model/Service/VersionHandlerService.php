<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class VersionHandlerService
 */
class VersionHandlerService
{
    private Config $config;
    private Curl $curl;
    private Reader $moduleDirReader;
    private File $fileDriver;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Config $config,
        Curl $curl,
        Reader $moduleDirReader,
        File $fileDriver,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->moduleDirReader = $moduleDirReader;
        $this->fileDriver = $fileDriver;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns type of version update
     *
     * @param string|null $currentVersion
     * @param string|null $latestVersion
     *
     * @return string
     */
    public function getVersionType(?string $currentVersion, ?string $latestVersion): string
    {
        $current = $this->getVersionParts($currentVersion);
        $latest = $this->getVersionParts($latestVersion);

        // Compare version numbers - major, minor and then revision
        for ($i = 0; $i < 3; $i++) {
            if ($current[$i] < $latest[$i]) {
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

        return '';
    }

    /**
     * Check if module needs updating
     *
     * @param string|null $currentVersion
     * @param string|null $latestVersion
     *
     * @return bool
     */
    public function needsUpdate(?string $currentVersion, ?string $latestVersion): bool
    {
        $current = $this->getVersionParts($currentVersion);
        $latest = $this->getVersionParts($latestVersion);

        // Compare version numbers - major, minor and then revision
        for ($i = 0; $i < 3; $i++) {
            if ($current[$i] < $latest[$i]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split a version string into three integer parts (major, minor, revision)
     *
     * Tolerates a null or malformed value and an optional leading "v",
     * padding any missing segment with 0 so comparisons never fail.
     *
     * @param string|null $version
     *
     * @return int[]
     */
    private function getVersionParts(?string $version): array
    {
        $parts = explode('.', ltrim((string)$version, 'vV'));

        return [
            (int)($parts[0] ?? 0),
            (int)($parts[1] ?? 0),
            (int)($parts[2] ?? 0),
        ];
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
        $gitApiUrl = $this->config->getValue('github_api_url', null, (string)$storeCode, ScopeInterface::SCOPE_STORE);

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
     * @param mixed[] $versions
     *
     * @return mixed|void
     */
    public function getLatestVersion(array $versions)
    {
        foreach ($versions as $version) {
            $tagName = $version['tag_name'] ?? null;
            // Find latest stable release, ignore any tag that is not a plain version number (beta or unsafe content)
            if (is_string($tagName) && preg_match('/^v?\d+(\.\d+){0,3}\z/', $tagName) === 1) {
                return $tagName;
            }
        }
    }

    /**
     * Get the module version
     *
     * Falls back to 0.0.0 when composer.json cannot be read or does not hold a
     * version, so the caller never gets a null or a fatal error.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function getModuleVersion(string $prefix = ''): string
    {
        // Build the composer file path
        $filePath = $this->moduleDirReader->getModuleDir(
                '',
                'CheckoutCom_Magento2'
            ) . '/composer.json';

        try {
            // Get the composer file content
            $fileContent = json_decode(
                $this->fileDriver->fileGetContents($filePath)
            );
        } catch (FileSystemException $e) {
            return $prefix . '0.0.0';
        }

        if (!is_object($fileContent) || !isset($fileContent->version) || !is_string($fileContent->version)) {
            return $prefix . '0.0.0';
        }

        return $prefix . $fileContent->version;
    }
}
