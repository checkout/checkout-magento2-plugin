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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Apm;

use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DisplayBic implements HttpGetActionInterface
{
    protected StoreManagerInterface $storeManager;
    protected ApiHandlerService $apiHandler;
    protected JsonFactory $jsonFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        ApiHandlerService $apiHandler,
        JsonFactory $jsonFactory
    ) {
        $this->storeManager = $storeManager;
        $this->apiHandler = $apiHandler;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @throws NoSuchEntityException
     * @throws CheckoutArgumentException
     * @throws CheckoutApiException
     */
    public function execute(): Json
    {
        // Get the store code
        $storeCode = $this->storeManager->getStore()->getCode();

        // Initialize the API handler
        $checkoutApi = $this->apiHandler
            ->init($storeCode, ScopeInterface::SCOPE_STORE)
            ->getCheckoutApi();

        $response = $checkoutApi->getIdealClient()->getIssuers();

        if (!isset($response['countries'])) {
            return $this->jsonFactory->create()->setData([]);
        }

        $issuers = [];

        foreach ($response['countries'] as $country) {
            foreach ($country['issuers'] as $value) {
                $issuers[] = $value;
            }
        }

        return $this->jsonFactory->create()->setData($issuers);
    }
}
