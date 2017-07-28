<?php

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use CheckoutCom\Magento2\Model\Adapter\CallbackEventAdapter;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;
use CheckoutCom\Magento2\Model\GatewayResponseTrait;
use CheckoutCom\Magento2\Model\Method\CallbackMethod;
use DomainException;

use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class CallbackService {

    use GatewayResponseTrait;

    /**
     * @var CallbackMethod
     */
    protected $callbackMethod;

    /**
     * @var CallbackEventAdapter
     */
    protected $eventAdapter;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * CallbackService constructor.
     * @param CallbackMethod $callbackMethod
     * @param CallbackEventAdapter $eventAdapter
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(CallbackMethod $callbackMethod, CallbackEventAdapter $eventAdapter, OrderFactory $orderFactory, OrderRepositoryInterface $orderRepository, GatewayConfig $gatewayConfig, InvoiceService $invoiceService, InvoiceRepositoryInterface $invoiceRepository) {
        $this->callbackMethod   = $callbackMethod;
        $this->eventAdapter     = $eventAdapter;
        $this->orderFactory     = $orderFactory;
        $this->orderRepository  = $orderRepository;
        $this->gatewayConfig    = $gatewayConfig;
        $this->invoiceService       = $invoiceService;
        $this->invoiceRepository    = $invoiceRepository;
    }

    /**
     * Runs the service.
     *
     * @throws DomainException
     * @throws LocalizedException
     */
    public function run() {
        if( ! $this->gatewayResponse) {
            throw new DomainException('The response is empty.');
        }

        $this->callbackMethod->setGatewayResponse($this->gatewayResponse);

        $commandName    = $this->getCommandName();
        $amount         = $this->getAmount();

        $this->callbackMethod->validate();

        $order      = $this->getAssociatedOrder();
        $payment    = $order->getPayment();

        if($payment instanceof Payment) {
            $infoInstance = $payment->getMethodInstance()->getInfoInstance();
            $this->callbackMethod->setInfoInstance($infoInstance);

            $this->putGatewayResponseToHolder();

            // Perform the required action on transaction
            switch($commandName) {
                case 'capture':
                    $this->callbackMethod->capture($payment, $amount);
                    break;
                case 'refund':
                    $this->callbackMethod->refund($payment, $amount);
                    break;
                case 'void':
                    $this->callbackMethod->void($payment);
                    break;
            }

            // Perform authorize complementary actions
            if ($commandName == 'authorize') {
                // Update order status
                $order->setState('new');
                $order->setStatus('pending');

                // Delete comments history
                foreach ($order->getAllStatusHistory() as $orderComment) {
                    $orderComment->delete();
                } 

                // Set email sent
                $order->setEmailSent(1);

                // Create new comment
                $newComment = 'Authorized amount of ' . ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']) . ' ' . $this->gatewayResponse['response']['message']['currency'] .' Transaction ID: ' . $this->gatewayResponse['response']['message']['id'];

                // Add the new comment
                $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);
            }

            // Perform capture complementary actions
            if ($commandName == 'capture') {
                // Update order status
                $order->setState('new');
                $order->setStatus($this->gatewayConfig->getNewOrderStatus());


                // Create new comment
                $newComment = 'Captured amount of ' . ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']) . ' ' . $this->gatewayResponse['response']['message']['currency'] .' Transaction ID: ' . $this->gatewayResponse['response']['message']['id'];

                // Add the new comment
                $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);
            }

            $this->orderRepository->save($order);
        }
    }

    /**
     * Returns the order instance.
     *
     * @return \Magento\Sales\Model\Order
     * @throws DomainException
     */
    private function getAssociatedOrder() {
        $orderId    = $this->gatewayResponse['response']['message']['trackId'];
        $order      = $this->orderFactory->create()->loadByIncrementId($orderId);

        if($order->isEmpty()) {
            throw new DomainException('The order does not exists.');
        }

        return $order;
    }

    /**
     * Returns the command name.
     *
     * @return null|string
     */
    private function getCommandName() {
        return $this->eventAdapter->getTargetCommandName($this->gatewayResponse['response']['eventType']);
    }

    /**
     * Returns the amount for the store.
     *
     * @return float
     */
    private function getAmount() {
        return ChargeAmountAdapter::getStoreAmountOfCurrency($this->gatewayResponse['response']['message']['value'], $this->gatewayResponse['response']['message']['currency']);
    }

    /**
     * Sets the gateway response to the holder.
     *
     * @return void
     */
    private function putGatewayResponseToHolder() {
        /* @var $gatewayResponseHolder GatewayResponseHolder */
        $gatewayResponseHolder = ObjectManager::getInstance()->get(GatewayResponseHolder::class);
        $gatewayResponseHolder->setGatewayResponse($this->gatewayResponse['response']['message']);
    }

}
