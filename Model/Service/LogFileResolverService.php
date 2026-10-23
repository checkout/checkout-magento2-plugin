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

use CheckoutCom\Magento2\Helper\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

/**
 * Resolve a log file name coming from the request to a readable file in var/log
 */
class LogFileResolverService
{
    /**
     * ACL resource required to read the log files in the admin
     *
     * @var string
     */
    public const ACL_RESOURCE = 'CheckoutCom_Magento2::logs';

    /**
     * Log files that can be displayed in the admin log viewer
     *
     * Keep in sync with the fields of the checkoutcom_logs group in etc/adminhtml/system.xml
     *
     * @var string[]
     */
    public const ALLOWED_FILES = [
        'checkoutcom_magento2.log',
        'exception.log',
    ];

    private Filesystem $filesystem;
    private Logger $logger;

    public function __construct(
        Filesystem $filesystem,
        Logger $logger
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
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

        $logDirectory = $this->filesystem->getDirectoryRead(DirectoryList::LOG);

        try {
            if (!$logDirectory->isFile($fileName)) {
                return null;
            }
        } catch (FileSystemException $fileSystemException) {
            $this->logger->error(
                sprintf('Unable to read log file %s: %s', $fileName, $fileSystemException->getMessage())
            );

            return null;
        }

        return $logDirectory->getAbsolutePath($fileName);
    }
}
