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

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use Checkout\CheckoutArgumentException;
use Checkout\Sources\Previous\SepaSourceRequest;
use Checkout\Sources\Previous\SourceData;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DisplaySepa
 */
class DisplaySepa extends Action
{
    /**
     * $pageFactory field
     *
     * @var PageFactory $pageFactory
     */
    private $pageFactory;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    private $jsonFactory;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $apiHandler field
     *
     * @var CheckoutApi $apiHandler
     */
    private $apiHandler;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $storeInformation field
     *
     * @var Information $storeInformation
     */
    private $storeInformation;
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $logger field
     *
     * @var Logger $logger
     */
    private $logger;
    /**
     * $storeModel field
     *
     * @var Store $storeModel
     */
    private $storeModel;

    /**
     * DisplaySepa constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param JsonFactory $jsonFactory
     * @param Config $config
     * @param ApiHandlerService $apiHandler
     * @param QuoteHandlerService $quoteHandler
     * @param Information $storeInformation
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Store $storeModel
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        JsonFactory $jsonFactory,
        Config $config,
        ApiHandlerService $apiHandler,
        QuoteHandlerService $quoteHandler,
        Information $storeInformation,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Store $storeModel
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->storeInformation = $storeInformation;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->storeModel = $storeModel;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     */
    public function execute(): Json
    {
        // Prepare the output container
        $html = '';

        // Run the requested task
        if ($this->isValidRequest()) {
            $html = $this->runTask();
        }

        return $this->jsonFactory->create()->setData(['html' => $html]);
    }

    /**
     * Checks if the request is valid.
     *
     * @return boolean
     */
    public function isValidRequest(): bool
    {
        return $this->getRequest()->isAjax() && $this->isValidApm() && $this->isValidTask();
    }

    /**
     * Checks if the requested APM is valid.
     *
     * @return boolean
     */
    protected function isValidApm(): bool
    {
        // Get the list of APM
        $apmEnabled = explode(
            ',',
            $this->config->getValue('apm_enabled', 'checkoutcom_apm') ?? ''
        );

        /** @var string $source */
        $source = $this->getRequest()->getParam('source');

        // Load block data for each APM
        return in_array($source, $apmEnabled);
    }

    /**
     * Checks if the task is valid.
     *
     * @return boolean
     */
    public function isValidTask(): bool
    {
        return method_exists($this, $this->buildMethodName());
    }

    /**
     * Builds a method name from request.
     *
     * @return string
     */
    protected function buildMethodName(): string
    {
        /** @var string $task */
        $task = $this->getRequest()->getParam('task');

        return 'get' . ucfirst($task);
    }

    /**
     * Runs the requested task.
     *
     * @return string
     */
    public function runTask(): string
    {
        $methodName = $this->buildMethodName();

        return $this->$methodName();
    }

    /**
     * Gets the mandate.
     *
     * @return string
     * @throws NoSuchEntityException|LocalizedException
     * @throws CheckoutArgumentException
     */
    public function getMandate(): string
    {
        $html = '';

        $sepa = $this->requestSepa();
        if ($sepa && $this->apiHandler->isValidResponse($sepa)) {
            $html = $this->loadBlock($sepa['response_data']['mandate_reference'], $sepa['_links']['sepa:mandate-get']['href']);
        }

        return $html;
    }

    /**
     * Request gateway to add new source.
     *
     * @return array|null
     * @throws CheckoutArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function requestSepa(): ?array
    {
        /** @var string $accountIban */
        $accountIban = $this->getRequest()->getParam('account_iban');
        /** @var CartInterface $quote */
        $quote = $this->quoteHandler->getQuote();
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->quoteHandler->getBillingAddress();

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $checkoutApi = $this->apiHandler
            ->init($storeCode, ScopeInterface::SCOPE_STORE)
            ->getCheckoutApi();

        // Build the address
        $address = $this->apiHandler->createBillingAddress($quote);

        // Build the SEPA data
        $data = new SourceData();
        $data->first_name = $billingAddress->getFirstname();
        $data->last_name = $billingAddress->getLastname();
        $data->bic = '';
        $data->account_iban = $accountIban;
        $data->billing_descriptor = $this->config->getStoreName();
        $data->mandate_type = 'single';

        // Build the customer
        $customer = $this->apiHandler->createCustomer($quote);

        try {
            // Build and add the source
            $source = new SepaSourceRequest();
            $source->billing_address = $address;
            $source->source_data = $data;
            $source->customer = $customer;

            return $checkoutApi->getSourcesClient()->createSepaSource($source);
        } catch (Exception $e) {
            $this->logger->write($e->getBody());

            return null;
        }
    }

    /**
     * Returns the SEPA mandate block
     *
     * @param string $reference
     * @param string $url
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function loadBlock(string $reference, string $url): string
    {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        /** @var DataObject $store */
        $store = $this->storeInformation->getStoreInformationObject($this->storeModel);

        return $this->pageFactory->create()
            ->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Apm\Sepa\Mandate')
            ->setTemplate('CheckoutCom_Magento2::payment/apm/sepa/mandate.phtml')
            ->setData('billingAddress', $billingAddress)
            ->setData('store', $store)
            ->setData('reference', $reference)
            ->setData('url', $url)
            ->toHtml();
    }
}
