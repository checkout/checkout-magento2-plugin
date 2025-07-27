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

namespace CheckoutCom\Magento2\Block\Adminhtml\Logs;

use LimitIterator;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;
use SplFileObject;

/**
 * Class View
 */
class View extends Template
{
    /**
     * Description getLogFile function
     *
     * @return string
     */
    public function getLogFile(): string
    {
        $file = BP . '/var/log/' . $this->_request->getParam('file');
        if (is_file($file)) {
            $file = new SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            if ($lastLine) {
                // Get the last 5000 lines of the log file for the preview
                $lines = new LimitIterator(
                    $file, ($lastLine - 5000) > 0 ? $lastLine - 5000 : 0, $lastLine
                );

                return implode('', iterator_to_array($lines));
            }
        }

        return '';
    }

    /**
     * Description getSizeMessage function
     *
     * @return false|Phrase
     */
    public function getSizeMessage(): Phrase | false
    {
        $file = BP . '/var/log/' . $this->_request->getParam('file');
        if (is_file($file)) {
            $file = new SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            if ($lastLine > 5000) {
                return __('The log file is too large to display in full. This preview displays the last 5000 lines');
            }
        }

        return false;
    }

    /**
     * Description getFileName function
     *
     * @return mixed
     */
    public function getFileName(): mixed
    {
        return $this->_request->getParam('file');
    }
}
