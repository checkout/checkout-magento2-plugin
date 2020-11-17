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

namespace CheckoutCom\Magento2\Controller\Apm;

use \Checkout\Models\Sources\Sepa;
use \Checkout\Models\Sources\SepaData;
use \Checkout\Models\Sources\SepaAddress;
use \Checkout\Library\Exceptions\CheckoutHttpException;

/**
 * Class DisplaySepa
 */
class DisplaySepa extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Context
     */
    public $context;

    /**
     * @var PageFactory
     */
    public $pageFactory;

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var CheckoutApi
     */
    public $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * @var Information
     */
    public $storeInformation;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Quote
     */
    public $quote;

    /**
     * @var Address
     */
    public $billingAddress;

    /**
     * @var Store
     */
    public $store;

    /**
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler,
        \Magento\Store\Model\Information $storeInformation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \Magento\Store\Model\Store $storeModel
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
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the output container
        $html = '';

        // Get the request parameters
        $this->source = $this->getRequest()->getParam('source');
        $this->task = $this->getRequest()->getParam('task');
        $this->bic = $this->getRequest()->getParam('bic');
        $this->account_iban = $this->getRequest()->getParam('account_iban');

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();
        $this->billingAddress = $this->quoteHandler->getBillingAddress();
        $this->store = $this->storeInformation->getStoreInformationObject($this->storeModel);

        // Run the requested task
        if ($this->isValidRequest()) {
            $html = $this->runTask();
        }

        return $this->jsonFactory->create()->setData(
            ['html' => $html]
        );
    }

    /**
     * Checks if the request is valid.
     *
     * @return boolean
     */
    public function isValidRequest()
    {
        return $this->getRequest()->isAjax()
        && $this->isValidApm()
        && $this->isValidTask();
    }

    /**
     * Checks if the task is valid.
     *
     * @return boolean
     */
    public function isValidTask()
    {
        return method_exists($this, $this->buildMethodName());
    }

    /**
     * Runs the requested task.
     *
     * @return string
     */
    public function runTask()
    {
        $methodName = $this->buildMethodName();
        return $this->$methodName();
    }

    /**
     * Builds a method name from request.
     *
     * @return string
     */
    public function buildMethodName()
    {
        return 'get' . ucfirst($this->task);
    }

    /**
     * Checks if the requested APM is valid.
     *
     * @return boolean
     */
    public function isValidApm()
    {
        // Get the list of APM
        $apmEnabled = explode(
            ',',
            $this->config->getValue('apm_enabled', 'checkoutcom_apm')
        );

        // Load block data for each APM
        return in_array($this->source, $apmEnabled) ? true : false;
    }

    /**
     * Returns the SEPA mandate block.
     *
     * @return string
     */
    public function loadBlock($reference, $url)
    {
        return $this->pageFactory->create()->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Apm\Sepa\Mandate')
            ->setTemplate('CheckoutCom_Magento2::payment/apm/sepa/mandate.phtml')
            ->setData('billingAddress', $this->billingAddress)
            ->setData('store', $this->store)
            ->setData('reference', $reference)
            ->setData('url', $url)
            ->toHtml();
    }

    /**
     * Gets the mandate.
     *
     * @return string
     */
    public function getMandate()
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
     */
    public function requestSepa()
    {
        $sepa = null;

        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $api = $this->apiHandler->init($storeCode);

        // Build the address
        $address = new SepaAddress(
            $this->billingAddress->getStreetLine(1),
            $this->billingAddress->getCity(),
            $this->billingAddress->getPostcode(),
            $this->billingAddress->getCountryId()
        );

        // Address line 2
        $address->address_line2 = $this->billingAddress->getStreetLine(2);

        // Build the SEPA data
        $data = new SepaData(
            $this->billingAddress->getFirstname(),
            $this->billingAddress->getLastname(),
            $this->account_iban,
            $this->bic,
            $this->config->getStoreName(),
            'single'
        );

        // Build the customer
        $customer = $this->apiHandler->createCustomer($this->quote);
        
        try {
            // Build and add the source
            $source = new Sepa($address, $data, $customer);
            $sepa = $api->checkoutApi
                ->sources()
                ->add($source);

            return $sepa;
        } catch (CheckoutHttpException $e) {
            $this->logger->write($e->getBody());
        }
    }
}
