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
     * @var BackendAuthSession
     */
    protected $backendAuthSession;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionHandler;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var String
     */
    protected $methodId;

    /**
     * OrderSaveAfter constructor.
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionHandler,
        \CheckoutCom\Magento2\Helper\Utilities $utilities
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->transactionHandler = $transactionHandler;
        $this->utilities = $utilities;
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer)
    {
        // Get the order
        $this->order = $observer->getEvent()->getOrder();

        // Get the method id
        $this->methodId = $this->order->getPayment()->getMethodInstance()->getCode();

        // Create the authorization transaction
        if ($this->needsMotoProcessing()) {
            $this->transactionHandler->createTransaction(
                $this->order,
                Transaction::TYPE_AUTH,
                $this->utilities->getPaymentData($this->order),
                false
            );
        }
        
        return $this;
    }

    /**
     * Checks if the MOTO logic should be triggered.
     */
    protected function needsMotoProcessing()
    {
        return $this->backendAuthSession->isLoggedIn()
        && $this->methodId == 'checkoutcom_moto'
        && !$this->transactionHandler->hasTransaction(
            Transaction::TYPE_AUTH,
            $this->order
        );
    }
}
