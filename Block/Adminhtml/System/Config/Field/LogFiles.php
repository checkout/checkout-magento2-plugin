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

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;

class LogFiles extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    const TEMPLATE = 'system/config/logfile_admin.phtml';

    /**
     * Set the template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Overridden method for rendering a field. In this case the field must be only for read.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $file = BP . '/var/log/' . $element->getLabel();
        $contents = '';
        
        $url = $this->getUrl('cko/logs/view', [
            'file' => $element->getLabel()
        ]);

        if (is_file($file)) 
        {
            // Get the last 50 lines of the log file for the preview
            $file = new \SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $last_line = $file->key();
            if ($last_line) {
                $lines = new \LimitIterator(
                    $file,
                    ($last_line - 50) > 0 ? $last_line - 50 : 0,
                    $last_line
                );
                $contents = implode('',iterator_to_array($lines));
            } else {
                $contents = '';
            }
        }

        $element->setData('value', $contents);
        $element->setReadonly('readonly');

        $this->addData(
            [
                'element_html' => $element->getElementHtml(),
                'button_url' => $url,
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Generate set webhook button html
     *
     * @return string
     */
    public function getButtonHtml($url)
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'view_more',
                'label' => __('View More'),
                'onclick' => 'setLocation(\''.$url.'\')'
            ]
        );

        return $button->toHtml();
    }
}
