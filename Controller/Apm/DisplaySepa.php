<?php

namespace CheckoutCom\Magento2\Controller\Apm;

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
     * Display constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
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
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the output container
        $html = '';

        // Run the requested task
        if ($this->isValidRequest()) {
            $this->runTask();
        }
    
        return $this->jsonFactory->create()->setData(
            ['html' => $html]
        );
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
     * Returns the SEPA mandate block.
     */
    protected function loadBlock()
    {
        return $this->pageFactory->create()->getLayout()
        ->createBlock('CheckoutCom\Magento2\Block\Apm\Form')
        ->setTemplate('CheckoutCom_Magento2::payment/apm/sepa/mandate.phtml')
        ->toHtml();
    }

    /**
     * Request the SEPA mandate.
     */
    public function getMandate() {
        // Todo - Send the mandate request using the SDK 
        $mandate = true;

        // Return the mandate content 
        if ($mandate) {
            return $this->loadBlock();
        }
    }
}
