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

use _HumbugBoxe8a38a0636f4\Nette\DI\Extensions\DIExtension;
use Checkout\Library\HttpHandler;
use Checkout\Models\Webhooks\WebhookHeaders;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use CheckoutCom\Magento2\Helper\Logger;

class Webhook extends Action
{

    protected $resultJsonFactory;

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var WriterInterface
     */
    public $configWriter;

    /**
     * @var TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

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
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Logger $logger
    ) {
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->apiHandler           = $apiHandler;
        $this->storeManager         = $storeManager;
        $this->scopeConfig          = $scopeConfig;
        $this->configWriter         = $configWriter;
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
            $storeCode = $this->storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

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
                $privateSharedKey = $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/private_shared_key',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $webhook = new \Checkout\Models\Webhooks\Webhook($webhookUrl, $webhookId);
                $webhook->event_types = $eventTypes;
                $response = $api->checkoutApi->webhooks()->update($webhook, true);
                
                $this->configWriter->save('settings/checkoutcom_configuration/private_shared_key', $response->headers['Authorization']);
            } else {
                $webhook = new \Checkout\Models\Webhooks\Webhook($webhookUrl);
                $response = $api->checkoutApi->webhooks()->register($webhook, $eventTypes);

                $this->configWriter->save('settings/checkoutcom_configuration/private_shared_key', $response->headers['authorization']);
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
                'message' => $message
            ]);
        }
    }
}
?>

