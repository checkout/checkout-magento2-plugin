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

namespace Checkoutcom\Magento2\Controller\Adminhtml\System\Config;

use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use CheckoutCom\Magento2\Helper\Logger;
use Magento\Config\Model\ResourceModel\Config;

class Webhook extends Action
{

    protected $resultJsonFactory;

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Config
     */
    public $resourceConfig;

    /**
     * @var TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiHandlerService $apiHandler,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->apiHandler           = $apiHandler;
        $this->scopeConfig          = $scopeConfig;
        $this->resourceConfig       = $resourceConfig;
        $this->cacheTypeList        = $cacheTypeList;
        $this->logger               = $logger;
        parent::__construct($context);
    }

    /**
     * Main controller function.
     *
     * @return JSON
     */
    public function execute()
    {
        try {
            // Prepare some parameters
            $message = '';
            // Get the store code
            $scope = $this->getRequest()->getParam('scope', 0);
            $storeCode = $this->getRequest()->getParam('scope_id', 0);
            $secretKey = $this->getRequest()->getParam('secret_key', 0);

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode, $scope, $secretKey);

            $webhookUrl = $this->scopeConfig->getValue(
                'payment/checkoutcom/module/account_settings/webhook_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $events = $api->checkoutApi->events()->types(['version' => '2.0']);
            $eventTypes = $events->list[0]->event_types;
            $webhooks = $api->checkoutApi->webhooks()->retrieve();
            $webhookId = null;
            foreach ($webhooks->list as $list) {
                if ($list->url == $webhookUrl) {
                    $webhookId = $list->id;
                }
            }

            if (isset($webhookId)) {
                $webhook = new \Checkout\Models\Webhooks\Webhook($webhookUrl, $webhookId);
                $webhook->event_types = $eventTypes;
                $response = $api->checkoutApi->webhooks()->update($webhook, true);
                $privateSharedKey = $response->headers->authorization;

                $this->resourceConfig->saveConfig(
                    'settings/checkoutcom_configuration/private_shared_key',
                    $response->headers->authorization,
                    $scope,
                    $storeCode
                );

                $this->resourceConfig->saveConfig(
                    'settings/checkoutcom_configuration/secret_key',
                    $secretKey,
                    $scope,
                    $storeCode
                );
            } else {
                $webhook = new \Checkout\Models\Webhooks\Webhook($webhookUrl);
                $response = $api->checkoutApi->webhooks()->register($webhook, $eventTypes);

                $this->resourceConfig->saveConfig(
                    'settings/checkoutcom_configuration/private_shared_key',
                    $response->headers->authorization,
                    $scope,
                    $storeCode
                );

                $this->resourceConfig->saveConfig(
                    'settings/checkoutcom_configuration/secret_key',
                    $secretKey,
                    $scope,
                    $storeCode
                );
            }
            $success = $response->isSuccessful();

            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        } catch (\Exception $e) {
            $success = false;
            $message = __($e->getMessage());
            $this->logger->write($message);
        } finally {
            return $this->resultJsonFactory->create()->setData([
                'success' => $success,
                'privateSharedKey' => isset($privateSharedKey) ? $privateSharedKey : '',
                'message' => 'Could not set webhooks, please check your account settings'
            ]);
        }
    }
}
