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

use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;

abstract class AbstractCallbackUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    const TEMPLATE = 'system/config/webhook_admin.phtml';

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

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
     * @param ApiHandlerService $apiHandler
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param array $data
     */
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
     * Overridden method for rendering a field. In this case the field must be only for read.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        // Get the selected scope and id
        if (array_key_exists('website', $this->getRequest()->getParams())) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            $storeCode = $this->getRequest()->getParam('website', 0);
        } else {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $storeCode = $this->getRequest()->getParam('store', 0);
            if ($storeCode == 0) {
                $scope = 'default';
                $storeCode = $this->getRequest()->getParam('site', 0);
            }
        }

        $baseUrl = $this->scopeConfig->getValue(
            'web/unsecure/base_url',
            $scope,
            $storeCode
        );
        $callbackUrl = $baseUrl . 'checkout_com/' . $this->getControllerUrl();
        
        try {
            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, $scope);

            $privateSharedKey = $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/private_shared_key',
                $scope,
                $storeCode
            );

            $secretKey = $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/secret_key',
                $scope,
                $storeCode
            );

            // Retrieve all configured webhooks
            $webhooks = $api->checkoutApi->webhooks()->retrieve();
            $webhook = null;
            foreach ($webhooks->list as $list) {
                if ($list->url == $callbackUrl) {
                    $webhook = $list;
                    $headers = array_change_key_case($webhook->headers);
                }
            }

            // Get available webhook events
            $events = $api->checkoutApi->events()->types(['version' => '2.0']);
            $eventTypes = $events->list[0]->event_types;

            if (!isset($webhook)
                || $webhook->event_types != $eventTypes
                || $headers['authorization'] != $privateSharedKey
            ) {
                // Webhook not configured
                $element->setData('value', $callbackUrl);
                $element->setReadonly('readonly');

                if (empty($secretKey)) {
                    $this->addData(
                        [
                            'element_html' => $element->getElementHtml(),
                            'button_label' => __('Set Webhooks'),
                            'hidden' => false,
                            'scope' => $scope,
                            'scope_id' => $storeCode,
                            'webhook_url' => $callbackUrl
                        ]
                    );
                } else {
                    $this->addData(
                        [
                            'element_html' => $element->getElementHtml(),
                            'button_label' => __('Set Webhooks'),
                            'message' => __('Attention, webhook not properly configured!'),
                            'message_class' => 'no-webhook',
                            'hidden' => false,
                            'scope' => $scope,
                            'scope_id' => $storeCode,
                            'webhook_url' => $callbackUrl
                        ]
                    );
                }
                return $this->_toHtml();
            } else {
                // Webhook configured
                $element->setData('value', $callbackUrl);
                $element->setReadonly('readonly');

                $this->addData(
                    [
                        'element_html'      => $element->getElementHtml(),
                        'message'           => __('Your webhook is all set!'),
                        'message_class'     => 'webhook-set',
                        'hidden'            => true
                    ]
                );
                return $this->_toHtml();
            }
        } catch (\Exception $e) {
            // Invalid secret key
            $element->setData('value', $callbackUrl);
            $element->setReadonly('readonly');

            $secretKey = $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/secret_key',
                $scope,
                $storeCode
            );
            
            if (empty($secretKey)) {
                $this->addData(
                    [
                        'element_html'      => $element->getElementHtml(),
                        'hidden'            => true,
                        'scope'             => $scope,
                        'scope_id'          => $storeCode,
                        'webhook_url' => $callbackUrl
                    ]
                );
            } else {
                $this->addData(
                    [
                        'element_html'      => $element->getElementHtml(),
                        'message'           => __('Attention, secret key incorrect!'),
                        'message_class'     => 'no-webhook',
                        'hidden'            => true,
                        'scope'             => $scope,
                        'scope_id'          => $storeCode,
                        'webhook_url' => $callbackUrl
                    ]
                );
            }

            return $this->_toHtml();
        }
    }

    /**
     * Return ajax url for set webhook button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('cko/system_config/webhook');
    }

    /**
     * Generate set webhook button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'webhook_button',
                'label' => __('Set Webhooks'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Returns the controller url.
     *
     * @return string
     */
    abstract public function getControllerUrl();
}
