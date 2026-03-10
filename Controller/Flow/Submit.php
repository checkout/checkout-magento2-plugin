<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Flow;

use CheckoutCom\Magento2\Model\Service\FlowSubmitService;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Submits Flow payment with reference (order id). Called from frontend handleSubmit.
 */
class Submit extends Action implements HttpPostActionInterface
{
    private FlowSubmitService $flowSubmitService;
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    public function __construct(
        Context $context,
        FlowSubmitService $flowSubmitService,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->flowSubmitService = $flowSubmitService;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $body = $this->getRequest()->getContent();
            $params = $body ? $this->serializer->unserialize($body) : [];

            $sessionId = $params['session_id'] ?? '';
            $sessionData = $params['session_data'] ?? '';
            $reference = $params['reference'] ?? '';

            if ($sessionId === '' || $sessionData === '' || $reference === '') {
                $result->setHttpResponseCode(400);

                return $result->setData(['error' => true, 'message' => __('Invalid request.')]);
            }

            $apiResponse = $this->flowSubmitService->submit($sessionId, $sessionData, $reference);

            return $result->setData($apiResponse);
        } catch (Exception $exception) {
            $this->logger->error(sprintf('%s: %s', __METHOD__, $exception->getMessage()));
            $result->setHttpResponseCode(422);

            return $result->setData([
                'error' => true,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
