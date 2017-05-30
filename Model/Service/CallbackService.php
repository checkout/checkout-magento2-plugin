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
     * CallbackService constructor.
     * @param CallbackMethod $callbackMethod
     * @param CallbackEventAdapter $eventAdapter
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(CallbackMethod $callbackMethod, CallbackEventAdapter $eventAdapter, OrderFactory $orderFactory, OrderRepositoryInterface $orderRepository) {
        $this->callbackMethod   = $callbackMethod;
        $this->eventAdapter     = $eventAdapter;
        $this->orderFactory     = $orderFactory;
        $this->orderRepository  = $orderRepository;
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
