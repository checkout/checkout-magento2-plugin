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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Logger;

use CheckoutCom\Magento2\Logger\Handler\InfoHandlerFactory;
use CheckoutCom\Magento2\Logger\Handler\LoggerFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ResponseHandler
{
    private const FILE_EXTENSION = '.log';
    private const FILE_NAME = 'checkoutcom_magento2_gateway_{{date}}';
    private const DATE_FORMAT = 'Ymd-H:i:s';

    public function __construct(
        private TimezoneInterface $timezone,
        private InfoHandlerFactory $infoHandlerFactory,
        private LoggerFactory $loggerFactory,
        private DirectoryList $directoryList
    ) {
    }

    public function log(string $message): void
    {
        $filePath = $this->getFilePath();

        $infoHandler = $this->infoHandlerFactory->create(
            [
                'fileName' => $filePath,
            ]
        );

        $logger = $this->loggerFactory->create(
            [
                'handlers' => [
                    'info' => $infoHandler,
                ],
            ]
        );

        $logger->info($message);
    }

    private function getFilePath(): string
    {
        return DIRECTORY_SEPARATOR . $this->directoryList->getDefaultConfig()[DirectoryList::LOG]['path'] . DIRECTORY_SEPARATOR . $this->getFilename(
            ) . self::FILE_EXTENSION;
    }

    private function getFilename(): string
    {
        return str_replace('{{date}}', $this->timezone->date()->format(self::DATE_FORMAT), self::FILE_NAME);
    }
}
