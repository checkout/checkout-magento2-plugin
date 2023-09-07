<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Logger;

use CheckoutCom\Magento2\Logger\Handler\InfoHandler;
use CheckoutCom\Magento2\Logger\Handler\InfoHandlerFactory;
use CheckoutCom\Magento2\Logger\Handler\LoggerFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ResponseHandler
{
    private const FILE_PATH = DIRECTORY_SEPARATOR . DirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . DirectoryList::LOG;
    private const FILE_EXTENSION = '.log';
    private const FILE_NAME = 'checkoutcom_magento2_gateway_{{date}}';
    private const DATE_FORMAT = 'Ymd-H:i:s';

    /**
     * @var TimezoneInterface $timezone
     */
    private $timezone;
    /**
     * @var InfoHandlerFactory $infoHandlerFactory
     */
    private $infoHandlerFactory;
    /**
     * @var LoggerFactory $loggerFactory
     */
    private $loggerFactory;

    public function __construct(
        TimezoneInterface $timezone,
        InfoHandlerFactory $infoHandlerFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->timezone = $timezone;
        $this->infoHandlerFactory = $infoHandlerFactory;
        $this->loggerFactory = $loggerFactory;
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
        return self::FILE_PATH . DIRECTORY_SEPARATOR . $this->getFilename() . self::FILE_EXTENSION;
    }

    private function getFilename(): string
    {
        return str_replace('{{date}}', $this->timezone->date()->format(self::DATE_FORMAT), self::FILE_NAME);
    }
}
