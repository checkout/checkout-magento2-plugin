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

use Exception;
use Zend_Controller_Request_Http;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;
use Magento\Framework\Exception\LocalizedException;
use CheckoutCom\Magento2\Model\Service\WebhookCallbackService;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Helper\Tools;

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
    public function __construct(Context $context, WebhookCallbackService $callbackService) {
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
        // Prepare the request and response containers
        $request    = new Zend_Controller_Request_Http();
        $response   = $this->resultFactory->create(ResultFactory::TYPE_JSON);

$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
$logger = new \Zend\Log\Logger();
$logger->addWriter($writer);
$logger->info(print_r($response, 1));

        // Reject non POST requests
        if (!$request->isPost()) {
            $response->setHttpResponseCode(WebException::HTTP_METHOD_NOT_ALLOWED);

            return $response;
        }

$logger->info('after non post');


        // Fetch teh response
        $data = json_decode(file_get_contents('php://input'), true);



$logger->info('after decode');


        // Reject empty data
        if ($data === null || empty($data)) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);

            return $response;
        }

$logger->info('after empty');


        // Reject invalid authorization
        $auth = $request->getHeader('Authorization');
        if (!$this->requestIsValid($auth)) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);

            return $response;
        }

$logger->info('after auth');

        // Prepare the data
        try {
            $this->callbackService->run($data);
            $response->setHttpResponseCode(WebResponse::HTTP_OK);
        }
        catch(LocalizedException $e) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $response->setData(['error_message' => $e->getLogMessage()]);
        }
        catch(Exception $e) {
            $response->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $response->setData(['error_message' => $e->getMessage()]);
        }

        return $response;
    }

    /**
     * Checks if the request is valid.
     */
    private function requestIsValid($authorization) {
        return $this->tools->privateSharedKeyIsValid($authorization);
    }    
}
