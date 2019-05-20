<?php

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApi;
use Checkout\Models\Product;
use Checkout\Library\HttpHandler;
use Checkout\Models\Sources\Sepa;
use Checkout\Models\Sources\Klarna;

class DisplayKlarna extends \Magento\Framework\App\Action\Action {

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
        \Magento\Store\Model\Store $store
    ) {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;

        // Get the request parameters
        $this->source = $this->getRequest()->getParam('source');
        $this->task = $this->getRequest()->getParam('task');
        $this->bic = $this->getRequest()->getParam('bic');
        $this->account_iban = $this->getRequest()->getParam('account_iban');

        // Try to load a quote
        $this->quoteHandler = $quoteHandler;
        $this->quote = $this->quoteHandler->getQuote();
        $this->billingAddress = $quoteHandler->getBillingAddress();
        $this->store = $storeManager->getStoreInformationObject($store);

        // SDK related
        $this->apiHandler = $apiHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the output container
        $res = '{}';

        // Run the requested task
        if ($this->isValidRequest()) {
            $res = $this->runTask();
        }

        return $this->jsonFactory->create()
                                 ->setData($res);
    }

    /**
     * Checks if the request is valid.
     */
    protected function isValidRequest() {
        return $this->getRequest()->isAjax()
        && $this->isValidApm()
        && $this->isValidTask();
    }

    /**
     * Checks if the task is valid.
     */
    protected function isValidTask() {
        return method_exists($this, $this->buildMethodName());
    }

    /**
     * Runs the requested task.
     */
    protected function runTask() {
        $methodName = $this->buildMethodName();
        return $this->$methodName();
    }

    /**
     * Builds a method name from request.
     */
    protected function buildMethodName() {
        return 'get' . ucfirst($this->task);
    }

    /**
     * Checks if the requested APM is valid.
     */
    protected function isValidApm() {
        // Get the list of APM
        $apmEnabled = explode(',',
            $this->config->getValue('apm_enabled', 'checkoutcom_apm')
        );

        // Load block data for each APM
       return in_array($this->source, $apmEnabled) ? true : false;
    }

    /**
     * Returns the Klarna mandate block.
     */
    protected function loadBlock($reference, $url)
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
     * @return     <type>  The mandate.
     */
    public function getMandate() {

        $response = array();
        $products = array();
        $tax = 0;
        foreach ($this->quote->getAllVisibleItems() as $item) {

            $product = new Product();
            $product->name = $item->getName();
            $product->quantity = $item->getQty();
            $product->unit_price = $item->getPriceInclTax() *100;
            $product->tax_rate = $item->getTaxPercent() *100;
            $product->total_amount = $item->getRowTotalInclTax() *100;
            $product->total_tax_amount = $item->getTaxAmount() *100;

            $tax += $product->total_tax_amount;
            $products []= $product;
            $response['products'] []= $product->getValues();

        }


//file_put_contents('/tmp/asdad.json', $this->billingAddress->toJson());


        $klarna = new Klarna($this->billingAddress->getCountry(),
                             $this->quote->getQuoteCurrencyCode(),
                             'en-GB',
                             $this->quote->getGrandTotal() *100,
                             $tax,
                             $products
                         );


file_put_contents('/tmp/asdad.json', json_encode($klarna->getValues()));

// handle error


        $source = $this->apiHandler->checkoutApi->sources()->add($klarna);
        $response['source'] = $source->getValues();

        $response['purchase_country'] = $this->billingAddress->getCountry();
        $response['currency'] = $this->quote->getQuoteCurrencyCode();
        $response['locale'] = 'en-GB';
        $response['amount'] = $this->quote->getGrandTotal() *100;
        $response['tax_amount'] = $tax;

        return $response;

    }



    /**
     * Methods
     */


}
