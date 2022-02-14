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

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Fieldset;

use CheckoutCom\Magento2\Model\Service\VersionHandlerService;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Helper\Js;

/**
 * Class Payment
 */
class Payment extends Fieldset
{
    /**
     * $_backendConfig field
     *
     * @var Config $_backendConfig
     */
    private $_backendConfig;
    /**
     * $versionHandler field
     *
     * @var VersionHandlerService $versionHandler
     */
    private $versionHandler;

    /**
     * Payment constructor
     *
     * @param Context               $context
     * @param Session               $authSession
     * @param Js                    $jsHelper
     * @param Config                $backendConfig
     * @param VersionHandlerService $versionHandler
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        VersionHandlerService $versionHandler,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        $this->versionHandler = $versionHandler;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Add custom css class
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getFrontendClass($element): string
    {
        $enabledString = $this->_isPaymentEnabled($element) ? ' enabled' : '';

        return parent::_getFrontendClass($element) . ' with-button' . $enabledString;
    }

    /**
     * Check whether current payment method is enabled
     *
     * @param AbstractElement $element
     *
     * @return bool
     */
    public function _isPaymentEnabled(AbstractElement $element): bool
    {
        $groupConfig   = $element->getGroup();
        $activityPaths = isset($groupConfig['activity_path']) ? $groupConfig['activity_path'] : [];

        if (!is_array($activityPaths)) {
            $activityPaths = [$activityPaths];
        }

        $isPaymentEnabled = false;
        foreach ($activityPaths as $activityPath) {
            $isPaymentEnabled = $isPaymentEnabled || (bool)(string)$this->_backendConfig->getConfigDataValue(
                    $activityPath
                );
        }

        return $isPaymentEnabled;
    }

    /**
     * Return header title part of html for payment solution
     *
     * @param AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws FileSystemException
     */
    protected function _getHeaderTitleHtml($element): string
    {
        $html = '<div class="config-heading" >';

        $groupConfig = $element->getGroup();

        $disabledAttributeString = $this->_isPaymentEnabled($element) ? '' : ' disabled="disabled"';
        $disabledClassString = $this->_isPaymentEnabled($element) ? '' : ' disabled';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container">' .
            $this->versionHandler->getModuleVersion('v') . '&nbsp;&nbsp;' .
            '<button type="button"' .
            $disabledAttributeString .
            ' class="button action-configure' .
            (empty($groupConfig['checkout_com_separator']) ? '' : ' checkout-com-separator') .
            $disabledClassString .
            '" id="' .
            $htmlId .
            '-head" onclick="ckoToggleSolution.call(this, \'' .
            $htmlId .
            "', '" .
            $this->getUrl(
                'adminhtml/*/state'
            ) . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        if (!empty($groupConfig['more_url'])) {
            $html .= '<a class="link-more" href="' . $groupConfig['more_url'] . '" target="_blank">' . __(
                'Learn More'
            ) . '</a>';
        }
        if (!empty($groupConfig['demo_url'])) {
            $html .= '<a class="link-demo" href="' . $groupConfig['demo_url'] . '" target="_blank">' . __(
                'View Demo'
            ) . '</a>';
        }

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return header comment part of html for payment solution
     *
     * @param AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element): string
    {
        return '';
    }

    /**
     * Get collapsed state on-load
     *
     * @param AbstractElement $element
     *
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element): bool
    {
        return false;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element): string
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.ckoToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    $$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
