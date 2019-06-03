<?php

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use Checkout\Library\HttpHandler;
use Checkout\Models\Sources\Sepa;
use Checkout\Models\Sources\SepaData;
use Checkout\Models\Sources\SepaAddress;

class DisplaySepa extends \Magento\Framework\App\Action\Action {

	/**
     * @var Context
     */
    protected $context;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutApi
     */
    protected $apiHandler;

    /**
     * @var QuoteHandlerService
     */
    protected $quoteHandler;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Address
     */
    protected $billingAddress;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var Logger
     */
    protected $logger;

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
        \Magento\Store\Model\Information $storeManager,
        \Magento\Store\Model\Store $store,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->apiHandler = $apiHandler;
        $this->quoteHandler = $quoteHandler;
        $this->logger = $logger;

        // Get the request parameters
        $this->source = $this->getRequest()->getParam('source');
        $this->task = $this->getRequest()->getParam('task');
        $this->bic = $this->getRequest()->getParam('bic');
        $this->account_iban = $this->getRequest()->getParam('account_iban');

        // Try to load a quote
        $this->quote = $this->quoteHandler->getQuote();
        $this->billingAddress = $quoteHandler->getBillingAddress();
        $this->store = $storeManager->getStoreInformationObject($store);

    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the output container
        $html = '';

        try {
            // Run the requested task
            if ($this->isValidRequest()) {
                $html = $this->runTask();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $this->jsonFactory->create()->setData(
                ['html' => $html]
            );
        }
    }

    /**
     * Checks if the request is valid.
     */
    protected function isValidRequest() {
        try {
            return $this->getRequest()->isAjax()
            && $this->isValidApm()
            && $this->isValidTask();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Checks if the task is valid.
     */
    protected function isValidTask() {
        try {
            return method_exists($this, $this->buildMethodName());
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Runs the requested task.
     */
    protected function runTask() {
        try {
            $methodName = $this->buildMethodName();
            return $this->$methodName();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }

    /**
     * Builds a method name from request.
     */
    protected function buildMethodName() {
        try {
            return 'get' . ucfirst($this->task);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }

    /**
     * Checks if the requested APM is valid.
     */
    protected function isValidApm() {
        try {
            // Get the list of APM
            $apmEnabled = explode(',',
                $this->config->getValue('apm_enabled', 'checkoutcom_apm')
            );

            // Load block data for each APM
            return in_array($this->source, $apmEnabled) ? true : false;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return false;
        }
    }

    /**
     * Returns the SEPA mandate block.
     */
    protected function loadBlock($reference, $url)
    {
        try {
            return $this->pageFactory->create()->getLayout()
            ->createBlock('CheckoutCom\Magento2\Block\Apm\Sepa\Mandate')
            ->setTemplate('CheckoutCom_Magento2::payment/apm/sepa/mandate.phtml')
            ->setData('billingAddress', $this->billingAddress)
            ->setData('store', $this->store)
            ->setData('reference', $reference)
            ->setData('url', $url)
            ->toHtml();
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return '';
        }
    }

    /**
     * Gets the mandate.
     *
     * @return     <type>  The mandate.
     */
    public function getMandate() {
        $html = ''; // @todo: return error message in HTML

        try {
            $sepa = $this->requestSepa();
            if ($sepa && $sepa->isSuccessful()) {
                $html = $this->loadBlock($sepa->response_data['mandate_reference'], $sepa->getSepaMandateGet());
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $html;
        }
    }

    /**
     * Request gateway to add new source.
     *
     * @return     Sepa
     */
    protected function requestSepa() {
        $sepa = null;
        try {
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

            // Build and addthe source
            $source = new Sepa($address, $data);
            $sepa = $this->apiHandler->checkoutApi
                ->sources()
                ->add($source);
        } catch(\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $sepa;
        }
    }
}
