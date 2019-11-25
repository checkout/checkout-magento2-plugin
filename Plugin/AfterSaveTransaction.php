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

namespace CheckoutCom\Magento2\Plugin;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class AfterSaveTransaction.
 */
class AfterSaveTransaction
{
    /**
     * @var Session
     */
    public $backendAuthSession;

    /**
     * @var TransactionHandlerService
     */
    public $transactionHandler;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var String
     */
    public $methodId;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * AfterSaveTransaction constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Registry $registry,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->registry = $registry;
        $this->transactionHandler = $transactionHandler;
        $this->config = $config;
    }

    /**
     * Update a transaction after save
     */
    public function beforeSave(TransactionInterface $transaction)
    {
        // Prepare the instance properties
        $this->init($transaction);

        // Open the transaction
        if ($this->needsOpening()) {
            $this->transaction->setIsClosed(0);
            $this->registry->register(
                $this->getRegistryFlag(),
                true
            );
        }

        return $transaction;
    }

    /**
     * Prepare the instance properties
     */
    public function init($transaction) {
        // Set the loaded transaction
        $this->transaction = $transaction;

        // Load the order
        $this->order = $this->transaction->getOrder();

        // Load the method ID
        $this->methodId = $this->order->getPayment()
        ->getMethodInstance()
        ->getCode();
    }

    /**
     * Get the registry flag
     */
    public function getRegistryFlag() {
        return 'backend_capture_opened_' . $this->transaction->getTxnId();
    }

    /**
     * Check if a capture transaction needs opening
     */
    public function needsOpening() {
        return $this->backendAuthSession->isLoggedIn()
        && in_array($this->methodId, $this->config->getMethodsList())
        && $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE
        && !$this->registry->registry($this->getRegistryFlag())
        && !$this->orderHasRefunds();
    }

    /**
     * Check if an order has refunds
     */
    public function orderHasRefunds() {
        // Load the refund transactions
        $refundTransactions = $this->transactionHandler->getTransactions(
            Transaction::TYPE_REFUND,
            $this->order,
            1
        );
        
        return empty($refundTransactions) ? false : true;
    }
}
