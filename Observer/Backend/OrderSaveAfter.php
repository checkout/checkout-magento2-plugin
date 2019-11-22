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

/**
 * Class OrderSaveAfter.
 */
class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
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
     * @var Registry
     */
    public $registry;

    /**
     * @var OrderHandlerService
     */
    public $orderHandler;

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
     * @var Array
     */
    public $params;

    /**
     * @var String
     */
    public $methodId;

    /**
     * @var Object
     */
    public $api;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \CheckoutCom\Magento2\Model\Service\OrderHandlerService $orderHandler,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->registry = $registry;
        $this->orderHandler = $orderHandler;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
        $this->utilities = $utilities;
    }

    /**
     * OrderSaveBefore constructor.
     */
    public function execute(Observer $observer)
    {
        // Prepare the instance properties
        $this->init($observer);

        // Run the logic
        if ($this->needsCaptureOpening()) {
            // Registry flag
            $registryFlag = 'capture_transaction_opened_' . $this->order->getId();

            // Process capture transactions needing opening
            $captureTransactions = $this->getCaptureTransactions();
            if ($captureTransactions) {
                // Loop through the transactions
                foreach ($captureTransactions as $transaction) {
                    $transaction->setIsClosed(0);
                    $transaction->save();
                }

                // Save the data
                $this->order->getPayment()->save();
                $this->order->save();
                $this->registry->register($registryFlag, true);
            }
        }

        return $this;
    }

    /**
     * Prepare the instance properties.
     */
    public function init($observer)
    {
        // Get the order
        $this->order = $observer->getEvent()->getOrder();

        // Get the method id
        $this->methodId = $this->order
        ->getPayment()
        ->getMethodInstance()
        ->getCode();
    }

    /**
     * Check if capture transactions opening is needed.
     */
    public function needsCaptureOpening()
    {
        // Prepare the registry flag
        $registryFlag = 'capture_transaction_opened_' . $this->order->getId();

        // Return the test
        return $this->backendAuthSession->isLoggedIn()
        && $this->hasCaptureTransactions()
        && !$this->registry->registry($registryFlag);
    }

    /**
     * Check if capture transactions are avaiable.
     */
    public function hasCaptureTransactions()
    {
        // Load capture transactions
        $captureTransactions = $this->transactionHandler->hasTransaction(
            Transaction::TYPE_CAPTURE,
            $this->order,
            1
        );

        return $captureTransactions ? true : false;
    }

    /**
     * Get capture transactions.
     */
    public function getCaptureTransactions()
    {
        // Load capture transactions
        $captureTransactions = $this->transactionHandler->hasTransaction(
            Transaction::TYPE_CAPTURE,
            $this->order,
            1
        );

        // Load refund transactions
        $refundTransactions = $this->transactionHandler->hasTransaction(
            Transaction::TYPE_REFUND,
            $this->order,
            1
        );

        // Return the test
        return !$refundTransactions ? $captureTransactions : null;
    }
}
