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

namespace CheckoutCom\Magento2\Block\Adminhtml\Logs;

use CheckoutCom\Magento2\Model\Service\LogFileResolverService;
use LimitIterator;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use SplFileObject;

/**
 * Class View
 */
class View extends Template
{
    /**
     * Maximum number of lines displayed in the preview
     *
     * @var int
     */
    private const MAX_LINES = 5000;

    private LogFileResolverService $logFileResolver;
    private ?string $filePath = null;
    private bool $isFilePathResolved = false;
    private ?SplFileObject $logFile = null;
    private ?int $lastLine = null;

    public function __construct(
        Context $context,
        LogFileResolverService $logFileResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->logFileResolver = $logFileResolver;
    }

    /**
     * Description getLogFile function
     *
     * @return string
     */
    public function getLogFile(): string
    {
        $lastLine = $this->getLastLine();
        if ($lastLine) {
            // Get the last 5000 lines of the log file for the preview
            $lines = new LimitIterator(
                $this->openLogFile(), ($lastLine - self::MAX_LINES) > 0 ? $lastLine - self::MAX_LINES : 0, $lastLine
            );

            return implode('', iterator_to_array($lines));
        }

        return '';
    }

    /**
     * Description getSizeMessage function
     *
     * @return false|Phrase
     */
    public function getSizeMessage()
    {
        if ($this->getLastLine() > self::MAX_LINES) {
            return __('The log file is too large to display in full. This preview displays the last 5000 lines');
        }

        return false;
    }

    /**
     * Description getFileName function
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->getFilePath() !== null ? (string)$this->_request->getParam('file') : '';
    }

    /**
     * Return the absolute path of the requested log file, or null if it is not an allowed log file
     *
     * @return string|null
     */
    private function getFilePath(): ?string
    {
        if (!$this->isFilePathResolved) {
            $this->filePath = $this->logFileResolver->getFilePath($this->_request->getParam('file'));
            $this->isFilePathResolved = true;
        }

        return $this->filePath;
    }

    /**
     * Open the requested log file once
     *
     * @return SplFileObject|null
     */
    private function openLogFile(): ?SplFileObject
    {
        if ($this->logFile === null) {
            $path = $this->getFilePath();
            if ($path !== null) {
                $this->logFile = new SplFileObject($path, 'r');
            }
        }

        return $this->logFile;
    }

    /**
     * Return the index of the last line of the requested log file, computed once
     *
     * @return int
     */
    private function getLastLine(): int
    {
        if ($this->lastLine === null) {
            $file = $this->openLogFile();
            if ($file === null) {
                $this->lastLine = 0;
            } else {
                $file->seek(PHP_INT_MAX);
                $this->lastLine = $file->key();
            }
        }

        return $this->lastLine;
    }
}
