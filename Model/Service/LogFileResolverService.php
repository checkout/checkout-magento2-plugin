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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * Resolve a log file name coming from the request to a readable file in var/log
 */
class LogFileResolverService
{
    /**
     * Log files that can be displayed in the admin log viewer
     *
     * @var string[]
     */
    public const ALLOWED_FILES = [
        'checkoutcom_magento2.log',
        'exception.log',
    ];

    private ReadInterface $logDirectory;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->logDirectory = $filesystem->getDirectoryRead(DirectoryList::LOG);
    }

    /**
     * Return the absolute path of an allowed log file, or null if the file is not allowed or does not exist
     *
     * @param mixed $fileName
     *
     * @return string|null
     */
    public function getFilePath($fileName): ?string
    {
        if (!is_string($fileName) || !in_array($fileName, self::ALLOWED_FILES, true)) {
            return null;
        }

        try {
            if (!$this->logDirectory->isFile($fileName)) {
                return null;
            }
        } catch (FileSystemException $e) {
            return null;
        }

        return $this->logDirectory->getAbsolutePath($fileName);
    }
}
