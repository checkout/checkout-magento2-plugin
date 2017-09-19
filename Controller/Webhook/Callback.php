<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;
use Magento\Framework\Exception\LocalizedException;
use CheckoutCom\Magento2\Model\Service\CallbackService;
use Zend_Controller_Request_Http;
use Exception;

class Callback extends Action {

    /**
     * @var CallbackService
     */
    protected $callbackService;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CallbackService $callbackService
     */
    public function __construct(Context $context, CallbackService $callbackService) {
        parent::__construct($context);

        $this->callbackService = $callbackService;
    }

    /**
     * Handles the controller method.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws Exception
     */
    public function execute() {
        $request    = new Zend_Controller_Request_Http();
        $response   = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if( ! $request->isPost() ) {
            $response->setHttpResponseCode(WebException::HTTP_METHOD_NOT_ALLOWED);

            return $response;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if($data === null) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);

            return $response;
        }

        if($this->isTestRequest($data)) {
            $response->setHttpResponseCode(WebResponse::HTTP_OK);

            return $response;
        }

        $preparedData = [
            'headers' => [
                'Authorization' => $request->getHeader('Authorization'),
            ],
            'response' => $data,
        ];

        try {
            $this->callbackService->setGatewayResponse($preparedData);
            $this->callbackService->run();

            $response->setHttpResponseCode(WebResponse::HTTP_OK);
        }
        catch(LocalizedException $e) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $response->setData(['error_message' => $e->getLogMessage()]);
            $this->messageManager->addErrorMessage($e->getLogMessage());
        }
        catch(Exception $e) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $response->setData(['error_message' => $e->getMessage()]);
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * Determines if the request has data for only test purpose.
     *
     * @param array $request
     * @return bool
     */
    private function isTestRequest(array $request) {
        $eventType = $request['eventType'];

        if($eventType === 'ping') {
            return true;
        }

        $id = $request['message']['id'];

        if($eventType === 'charge.succeeded' AND $id === 'charge_100002') {
            return true;
        }

        return ($eventType === 'charge.failed' AND $id === 'charge_100003');
    }

}
