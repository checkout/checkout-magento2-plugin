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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Payment;

use CheckoutCom\Magento2\Helper\Logger;
use CheckoutCom\Magento2\Model\Exception\CkoException;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractPayment extends Action
{
    protected $messageManager;
    protected ApiHandlerService $apiHandler;
    protected Logger $logger;
    protected OrderHandlerService $orderHandler;
    protected Session $session;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        
        ApiHandlerService $apiHandler,
        Context $context,
        Logger $logger,
        ManagerInterface $messageManager,
        OrderHandlerService $orderHandler,
        Session $session,
        StoreManagerInterface $storeManager,
    ) {
        parent::__construct($context);

        $this->apiHandler = $apiHandler;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->orderHandler = $orderHandler;
        $this->session = $session;
        $this->storeManager = $storeManager;
    }

    public function execute(): ResponseInterface
    {
        try {
            $urlParameters = $this->getUrlParameters();
            $apiCallResponse = $this->requestCkoApi($urlParameters);

            // Case save card
            if(isset($apiCallResponse['isSaveCard']) && $apiCallResponse['isSaveCard']) {
                return $this->saveCardAction($apiCallResponse);
            }

            $this->validateResponse($apiCallResponse);

            $order = $this->getOrder($apiCallResponse);

            return $this->paymentAction($apiCallResponse, $order);

        } catch (CkoException $e) {
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error has occurred, please select another payment method or retry in a few minutes')
            );

            $this->logger->write($e->getMessage());
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }

    /**
     * @throws CkoException
     */
    protected function getUrlParameters(): array {
        $sessionId = $this->getRequest()->getParam('cko-session-id', null);

        $reference = $this->getRequest()->getParam('reference', null);

        if (empty($reference) && empty($sessionId)) {
            throw new CkoException(__('Invalid request. No session ID or reference found.'));
        }

        return [
            "sessionId" => $sessionId ?? '',
            "reference" => $reference ?? ''
        ];
    }

    /**
     * @throws CheckoutApiException
     */
    protected function requestCkoApi($urlParameters): array {
        $storeCode = $this->storeManager->getStore()->getCode();
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);

        return $urlParameters['sessionId'] ? $api->getDetailsFromSessionId($urlParameters['sessionId']) : $api->getDetailsFromReference($urlParameters['reference']);
    }

    /**
     * @throws CkoException
     */
    protected function validateResponse($apiCallResponse): void {
        if (!isset($apiCallResponse['response']) || !$this->apiHandler->isValidResponse($apiCallResponse['response'])) {
            $this->session->restoreQuote();

            throw new CkoException(__('The transaction could not be processed.'));
        }
    }

    /**
     * @throws CkoException
     */
    protected function getOrder(array $apiCallResponse): OrderInterface {
        $order = $this->orderHandler->getOrder([
            'increment_id' => $apiCallResponse['orderId'],
        ]);

        if (!$this->orderHandler->isOrder($order)) {
            throw new CkoException(__('Invalid request. No order found.'));
        }

        return $order;
    }

    abstract protected function saveCardAction(array $apiCallResponse): ResponseInterface;

    abstract protected function paymentAction(array $apiCallResponse, OrderInterface $order): ResponseInterface;
}
