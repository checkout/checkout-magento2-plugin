<?php

namespace CheckoutCom\Magento2\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use CheckoutCom\Magento2\Model\GatewayResponseHolder;

class WebHookCommand implements CommandInterface {

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     *@var GatewayResponseHolder 
     */
    protected $gatewayResponseHolder;

    /**
     * WebHookCommand constructor.
     * @param HandlerInterface $handler
     * @param GatewayResponseHolder $gatewayResponseHolder
     */
    public function __construct(HandlerInterface $handler, GatewayResponseHolder $gatewayResponseHolder) {
        $this->handler                  = $handler;
        $this->gatewayResponseHolder    = $gatewayResponseHolder;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws CommandException
     */
    public function execute(array $commandSubject) {
        $response = $this->gatewayResponseHolder->getGatewayResponse();

        $this->handler->handle($commandSubject, $response);
    }

}
