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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Klarna;

use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class GetCustomerDatas
 */
class GetCustomerDatas implements HttpPostActionInterface
{
    public function __construct(
        protected JsonFactory $resultJsonFactory,
        protected RequestInterface $request,
        protected QuoteHandlerService $quoteHandlerService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        // Get the request data
        $quoteId = $this->request->getParam('quote_id');
        $storeId = $this->request->getParam('store_id');

        // Try to load a quote
        $quote = $this->quoteHandlerService->getQuote([
            'entity_id' => $quoteId,
            'store_id' => $storeId,
        ]);

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'billing' => $this->quoteHandlerService->getBillingAddress()->toArray(),
            ]
        );

        return $resultJson;
    }
}
