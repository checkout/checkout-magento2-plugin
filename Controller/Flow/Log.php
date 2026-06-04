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

namespace CheckoutCom\Magento2\Controller\Flow;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Lightweight frontend -> server logging endpoint for Flow diagnostics. Used to surface
 * client-side conditions that engineers should see in the Checkout.com log file — e.g. an APM
 * enabled in admin (Alternative Payments for Flow) that Checkout.com reports as unavailable
 * (isAvailable() === false). The injected logger is the dedicated checkoutcom_magento2_logger
 * (see di.xml), so entries land in the plugin's log channel.
 */
class Log extends Action implements HttpPostActionInterface
{
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
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
            $content = $this->getRequest()->getContent();
            $data = $content ? $this->serializer->unserialize($content) : [];

            $message = isset($data['message']) ? (string)$data['message'] : '';
            $context = (isset($data['context']) && is_array($data['context'])) ? $data['context'] : [];
            $level = isset($data['level']) ? (string)$data['level'] : 'warning';

            if ($message === '') {
                return $result->setData(['success' => false, 'message' => __('Empty log message.')]);
            }

            $message = '[Checkout.com Flow] ' . $message;

            if ($level === 'error') {
                $this->logger->error($message, $context);
            } elseif ($level === 'info') {
                $this->logger->info($message, $context);
            } else {
                $this->logger->warning($message, $context);
            }
        } catch (Exception $exception) {
            $this->logger->error(sprintf('%s: %s', __METHOD__, $exception->getMessage()));

            return $result->setData(['success' => false]);
        }

        return $result->setData(['success' => true]);
    }
}
