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

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Payment\Transaction;
use \Checkout\Models\Payments\TokenSource;
use \Checkout\Models\Payments\IdSource;
use \Checkout\Models\Payments\Payment;
use \Checkout\Models\Payments\BillingDescriptor;
use \Checkout\Models\Payments\Capture;

/**
 * Class OrderPaymentVoid.
 */
class OrderPaymentVoid implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var ApiHandlerService
     */
    public $apiHandler;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Array
     */
    public $params;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var String
     */
    public $methodId;

    /**
     * @var String
     */
    public $storeCode;

    /**
     * @var Object
     */
    public $api;

    /**
     * @var Object
     */
    public $payment;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        \CheckoutCom\Magento2\Model\Service\ApiHandlerService $apiHandler,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactionSearch
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->apiHandler = $apiHandler;
        $this->orderHandler = $orderHandler;
        $this->vaultHandler = $vaultHandler;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
        $this->utilities = $utilities;
        $this->logger = $logger;
        $this->transactionSearch = $transactionSearch;
    }

    /**
     * OrderSaveBefore constructor.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $order = $observer->getEvent()->getOrder();

        // Get the list of transactions
        $transactions = $this->transactionSearch
        ->create()
        ->addOrderIdFilter($order->getId());
        $transactions->getItems();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/trans.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        foreach ($transactions as $transaction) {
            $logger->info(print_r($transaction->toJson(), 1));
        }
    }
}
