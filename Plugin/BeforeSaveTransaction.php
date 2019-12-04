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
class BeforeSaveTransaction
{
    /**
     * @var RequestInterface
     */
    public $request;

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
     * BeforeSaveTransaction constructor.
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Registry $registry,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    ) {
        $this->request = $request;
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

        if ($this->needsCaptureProcessing()) {
        // Process the capture action
        $this->transaction->setIsClosed(0);
            $this->registry->register(
                $this->getRegistryFlag('capture'),
                true
            );
        }
        elseif ($this->needsRefundProcessing()) {
            // Process the refund aaction
            $this->transaction->setIsClosed(0);
            $this->registry->register(
                $this->getRegistryFlag('refund'),
                true
            );
        }

        return $transaction;
    }

    /**
     * Prepare the instance properties
     */
    public function init($transaction) {
        // Get the request parameters
        $this->request->getParams();

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
    public function getRegistryFlag($type) {
        return 'backend_' . $type . '_opened_' . $this->transaction->getTxnId();
    }

    /**
     * Check if a capture action needs processing
     */
    public function needsCaptureProcessing() {
        return $this->backendAuthSession->isLoggedIn()
        && in_array($this->methodId, $this->config->getMethodsList())
        && $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE
        && !$this->registry->registry($this->getRegistryFlag('capture'))
        && isset($this->params['invoice']['capture_case'])
        && $this->params['invoice']['capture_case'] == 'online';
    }


    /**
     * Check if a refund action needs processing
     */
    public function needsRefundProcessing() {
        return $this->backendAuthSession->isLoggedIn()
        && in_array($this->methodId, $this->config->getMethodsList())
        && $this->transaction->getTxnType() == Transaction::TYPE_REFUND
        && !$this->registry->registry($this->getRegistryFlag('refund'))
        && isset($this->params['creditmemo']) && isset($this->params['creditmemo']['do_offline'])
        && $this->params['creditmemo']['do_offline'] == 0;
    }
}
