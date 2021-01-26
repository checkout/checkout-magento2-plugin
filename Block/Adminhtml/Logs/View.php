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

namespace CheckoutCom\Magento2\Block\Adminhtml\Logs;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    public function getLogFile()
    {
        $file = BP . '/var/log/' . $this->_request->getParam('file');
        if (is_file($file)) {
            $file = new \SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $last_line = $file->key();
            if ($last_line) {
                // Get the last 5000 lines of the log file for the preview
                $lines = new \LimitIterator(
                    $file,
                    ($last_line - 5000) > 0 ? $last_line - 5000 : 0,
                    $last_line
                );
                return implode('',iterator_to_array($lines));
            }
        }
        return '';
    }
    
    public function getSizeMessage()
    {
        $file = BP . '/var/log/' . $this->_request->getParam('file');
        if (is_file($file)) {
            $file = new \SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $last_line = $file->key();
            if ($last_line > 5000) {
                return __('The log file is too large to display in full. This preview displays the last 5000 lines');
            }
        }
        return false;
    }

    public function getFileName()
    {
        return $this->_request->getParam('file');
    }
}
