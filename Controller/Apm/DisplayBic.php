<?php

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

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class DisplayBic implements HttpGetActionInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ApiHandlerService
     */
    protected $apiHandler;
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

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
    public function execute()
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
