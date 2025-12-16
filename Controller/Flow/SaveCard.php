<?php

declare(strict_types=1);
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

namespace CheckoutCom\Magento2\Controller\Flow;

use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class SaveCard implements HttpPostActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected RequestInterface $request;
    protected Session $checkoutSession;
    protected CartRepositoryInterface $quoteRepository;

    public function __construct(
        JsonFactory $resultJsonFactory,
        RequestInterface $request,
        Session $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $saveCardValue = (bool)$this->request->getParam('save');
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return $this->resultJsonFactory->create()->setData([
                'error' => __('No quote found for current session'),
            ]);
        }
        try {
            $quote->setData(FlowGeneralSettings::SALES_ATTRIBUTE_SHOULD_SAVE_CARD, $saveCardValue);
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            return $this->resultJsonFactory->create()->setData([
                'error' => __('Erreur while saving cart %1: %2', $quote->getId(), $e->getMessage()),
            ]);
        }

        return $this->resultJsonFactory->create()->setData(
            [
                'success' => true,
            ]
        );
    }
}
