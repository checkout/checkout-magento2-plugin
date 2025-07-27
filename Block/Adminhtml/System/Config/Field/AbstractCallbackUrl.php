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
     */
    const string TEMPLATE = 'system/config/webhook_admin.phtml';

    public function __construct(
        private ApiHandlerService $apiHandler,
        private ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
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

        $service = $this->scopeConfig->getValue(ConfigService::SERVICE_CONFIG_PATH, $scope, $storeCode);

        if ($service === ConfigService::SERVICE_ABC) {
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
                $webhooks = $api->getCheckoutApi()->getWebhooksClient()->retrieveWebhooks();
                if ($this->apiHandler->isValidResponse($webhooks)) {
                    $webhook = null;
                    foreach ($webhooks['items'] as $list) {
                        if ($list['url'] === $callbackUrl) {
                            $webhook = $list;
                            $headers = array_change_key_case($webhook['headers']);
                        }
                    }

                    // Get available webhook events
                    $eventTypes = $webhooks['items'][0]['event_types'];

                    if (!isset($webhook)
                        || $webhook['event_types'] != $eventTypes
                        || $headers['authorization'] != $privateSharedKey
                    ) {
                        // Webhook not configured
                        $element->setData('value', $callbackUrl);
                        $element->setReadonly('readonly');

                        if (empty($secretKey)) {
                            $this->addData([
                                'element_html' => $element->getElementHtml(),
                                'button_label' => __('Set Webhooks'),
                                'hidden' => false,
                                'scope' => $scope,
                                'scope_id' => $storeCode,
                                'webhook_url' => $callbackUrl,
                            ]);
                        } else {
                            $this->addData([
                                'element_html' => $element->getElementHtml(),
                                'button_label' => __('Set Webhooks'),
                                'message' => __('Attention, webhook not properly configured!'),
                                'message_class' => 'no-webhook',
                                'hidden' => false,
                                'scope' => $scope,
                                'scope_id' => $storeCode,
                                'webhook_url' => $callbackUrl,
                            ]);
                        }

                        return $this->_toHtml();
                    } else {
                        // Webhook configured
                        $element->setData('value', $callbackUrl);
                        $element->setReadonly('readonly');

                        $this->addData([
                            'element_html' => $element->getElementHtml(),
                            'message' => __('Your webhook is all set!'),
                            'message_class' => 'webhook-set',
                            'hidden' => true,
                            'scope' => $scope,
                            'scope_id' => $storeCode,
                            'webhook_url' => $callbackUrl,
                        ]);

                        return $this->_toHtml();
                    }
                }
            } catch (CheckoutApiException | CheckoutAuthorizationException | Exception $e) {
                // Invalid secret key
                $element->setData('value', $callbackUrl);
                $element->setReadonly('readonly');

                $secretKey = $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/secret_key',
                    $scope,
                    $storeCode
                );

                if (empty($secretKey)) {
                    $this->addData([
                        'element_html' => $element->getElementHtml(),
                        'hidden' => true,
                        'scope' => $scope,
                        'scope_id' => $storeCode,
                        'webhook_url' => $callbackUrl,
                    ]);
                } else {
                    $this->addData([
                        'element_html' => $element->getElementHtml(),
                        'message' => __('Attention, secret key incorrect!'),
                        'message_class' => 'no-webhook',
                        'hidden' => true,
                        'scope' => $scope,
                        'scope_id' => $storeCode,
                        'webhook_url' => $callbackUrl,
                    ]);
                }
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
