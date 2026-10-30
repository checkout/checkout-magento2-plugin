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

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Fieldset;

use CheckoutCom\Magento2\Model\Service\LogFileResolverService;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class LogFiles
 */
class LogFiles extends Fieldset
{
    /**
     * Hide the Log File Reader group from admin users who are not allowed to read log files
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        if (!$this->_authorization->isAllowed(LogFileResolverService::ACL_RESOURCE)) {
            return '';
        }

        return parent::render($element);
    }
}
