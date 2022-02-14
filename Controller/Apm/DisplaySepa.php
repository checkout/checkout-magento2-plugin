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
use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Models\Sources\Sepa;
use Checkout\Models\Sources\SepaAddress;
use Checkout\Models\Sources\SepaData;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
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
     * @param Context               $context
     * @param PageFactory           $pageFactory
     * @param JsonFactory           $jsonFactory
     * @param Config                $config
     * @param ApiHandlerService     $apiHandler
     * @param QuoteHandlerService   $quoteHandler
     * @param Information           $storeInformation
     * @param StoreManagerInterface $storeManager
     * @param Logger                $logger
     * @param Store                 $storeModel
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

        $this->pageFactory      = $pageFactory;
        $this->jsonFactory      = $jsonFactory;
        $this->config           = $config;
        $this->apiHandler       = $apiHandler;
        $this->quoteHandler     = $quoteHandler;
        $this->storeInformation = $storeInformation;
        $this->storeManager     = $storeManager;
        $this->logger           = $logger;
        $this->storeModel       = $storeModel;
    }

    /**
     * Handles the controller method
     *
     * @return Json
     * @throws NoSuchEntityException
     * @throws LocalizedException
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
     * Checks if the task is valid.
     *
     * @return boolean
     */
    public function isValidTask(): bool
    {
        return method_exists($this, $this->buildMethodName());
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
     * Checks if the requested APM is valid.
     *
     * @return boolean
     */
    protected function isValidApm(): bool
    {
        // Get the list of APM
        $apmEnabled = explode(
            ',',
            $this->config->getValue('apm_enabled', 'checkoutcom_apm')
        );

        /** @var string $source */
        $source = $this->getRequest()->getParam('source');

        // Load block data for each APM
        return in_array($source, $apmEnabled);
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

    /**
     * Gets the mandate.
     *
     * @return string
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getMandate(): string
    {
        $html = '';

        $sepa = $this->requestSepa();
        if ($sepa && $sepa->isSuccessful()) {
            $html = $this->loadBlock($sepa->response_data['mandate_reference'], $sepa->getSepaMandateGet());
        }

        return $html;
    }

    /**
     * Request gateway to add new source.
     *
     * @return Sepa
     * @throws NoSuchEntityException|LocalizedException
     */
    protected function requestSepa(): Sepa
    {
        /** @var string $bic */
        $bic = $this->getRequest()->getParam('bic');
        /** @var string $accountIban */
        $accountIban = $this->getRequest()->getParam('account_iban');
        /** @var CartInterface $quote */
        $quote = $this->quoteHandler->getQuote();
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->quoteHandler->getBillingAddress();
        $sepa = null;

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $checkoutApi = $this->apiHandler
            ->init($storeCode)
            ->getCheckoutApi();

        // Build the address
        $address = new SepaAddress(
            $billingAddress->getStreetLine(1),
            $billingAddress->getCity(),
            $billingAddress->getPostcode(),
            $billingAddress->getCountryId()
        );

        // Address line 2
        $address->address_line2 = $billingAddress->getStreetLine(2);

        // Build the SEPA data
        $data = new SepaData(
            $billingAddress->getFirstname(),
            $billingAddress->getLastname(),
            $accountIban,
            $bic,
            $this->config->getStoreName(),
            'single'
        );

        // Build the customer
        $customer = $this->apiHandler->createCustomer($quote);

        try {
            // Build and add the source
            $source = new Sepa($address, $data, $customer);
            $sepa   = $checkoutApi->sources()->add($source);

            return $sepa;
        } catch (CheckoutHttpException $e) {
            $this->logger->write($e->getBody());
        }
    }
}
