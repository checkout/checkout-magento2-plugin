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

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use Checkout\CheckoutApiException;
use Checkout\CheckoutAuthorizationException;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigService;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractCallbackUrl
 */
abstract class AbstractCallbackUrl extends Field
{
    /**
     * TEMPLATE constant
     *
     * @var string TEMPLATE
     */
    const TEMPLATE = 'system/config/webhook_admin.phtml';
    private ApiHandlerService $apiHandler;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ApiHandlerService $apiHandler,
        ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->apiHandler = $apiHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return ajax url for set webhook button
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('cko/system_config/webhook');
    }

    /**
     * Generate set webhook button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData([
            'id' => 'webhook_button',
            'label' => __('Set Webhooks'),
        ]);

        return $button->toHtml();
    }

    /**
     * Set the template
     *
     * @return AbstractCallbackUrl
     */
    protected function _prepareLayout(): AbstractCallbackUrl
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }

        return $this;
    }

    /**
     * Overridden method for rendering a field. In this case the field must be only for read.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        // Get the selected scope and id
        if (array_key_exists('website', $this->getRequest()->getParams())) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $storeCode = $this->getRequest()->getParam('website', 0);
        } else {
            $scope = ScopeInterface::SCOPE_STORES;
            $storeCode = $this->getRequest()->getParam('store', 0);
            if ($storeCode == 0) {
                $scope = 'default';
                $storeCode = $this->getRequest()->getParam('site', 0);
            }
        }

        return $this->_toHtml();
    }

    /**
     * Returns the controller url.
     *
     * @return string
     */
    abstract public function getControllerUrl(): string;
}
