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
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Model\InstantPurchase;

/**
 * Class CustomerData
 */
class CustomerData implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var InstantPurchaseInterface
     */
    private $instantPurchase;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerAddressesFormatter
     */
    private $customerAddressesFormatter;

    /**
     * @var ShippingMethodFormatter
     */
    private $shippingMethodFormatter;

    /**
     * @var VaultHandlerService
     */
    private $vaultHandler;

    /**
     * @var AvailabilityChecker
     */
    private $availabilityChecker;

    /**
     * @var PaymentTokenFormatter
     */
    private $paymentTokenFormatter;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * InstantPurchase constructor.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\InstantPurchase\Model\InstantPurchaseInterface $instantPurchase,
        \Magento\Customer\Model\Session $customerSession,
        \CheckoutCom\Magento2\Model\InstantPurchase\TokenFormatter $paymentTokenFormatter,
        \Magento\InstantPurchase\Model\Ui\CustomerAddressesFormatter $customerAddressesFormatter,
        \Magento\InstantPurchase\Model\Ui\ShippingMethodFormatter $shippingMethodFormatter,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler,
        \CheckoutCom\Magento2\Model\InstantPurchase\AvailabilityChecker $availabilityChecker,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->instantPurchase = $instantPurchase;
        $this->customerSession = $customerSession;
        $this->customerAddressesFormatter = $customerAddressesFormatter;
        $this->shippingMethodFormatter = $shippingMethodFormatter;
        $this->vaultHandler = $vaultHandler;
        $this->availabilityChecker = $availabilityChecker;
        $this->paymentTokenFormatter = $paymentTokenFormatter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData(): array
    {
        // Set the instant purchase availability
        $data = ['available' => $this->isAvailable()];
        if (!$this->isAvailable()) {
            return $data;
        }
        
        try {
            // Prepare the required data
            $this->prepareData();

            // Check if the option can be displayed
            if (!$this->canDisplay()) {
                return $data;
            }

            // Build the instant purchase data
            $data += [
                'paymentToken' => [
                    'publicHash' => $this->paymentToken->getPublicHash(),
                    'summary' => $this->paymentTokenFormatter->formatPaymentToken($this->paymentToken),
                ],
                'shippingAddress' => [
                    'id' => $this->shippingAddress->getId(),
                    'summary' => $this->customerAddressesFormatter->format($this->shippingAddress),
                ],
                'billingAddress' => [
                    'id' => $this->billingAddress->getId(),
                    'summary' => $this->customerAddressesFormatter->format($this->billingAddress),
                ],
                'shippingMethod' => [
                    'carrier' => $this->shippingMethod->getCarrierCode(),
                    'method' => $this->shippingMethod->getMethodCode(),
                    'summary' => $this->shippingMethodFormatter->format($this->shippingMethod),
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $data;
        }
    }

    /**
     * Prepare the data needed for instant purchase
     */
    protected function prepareData()
    {
        try {
            // Get the  payment token
            $this->paymentToken = $this->vaultHandler->getLastSavedCard();

            // Get the instant purchase option
            $this->instantPurchaseOption = $this->loadOption();

            // Get the shipping and billing data
            if ($this->instantPurchaseOption) {
                $this->shippingAddress = $this->instantPurchaseOption->getShippingAddress();
                $this->billingAddress = $this->instantPurchaseOption->getBillingAddress();
                $this->shippingMethod = $this->instantPurchaseOption->getShippingMethod();
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    /**
     * Load the instant purchase option
     */
    protected function loadOption()
    {
        try {
            return $this->instantPurchase->getOption(
                $this->storeManager->getStore(),
                $this->customerSession->getCustomer()
            );
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Checks if the instant purchase option is available
     */
    protected function isAvailable()
    {
        return $this->availabilityChecker->isAvailable();
    }

    /**
     * Checks if the instant purchase option can be displayed
     */
    protected function canDisplay()
    {
        return $this->customerSession->isLoggedIn()
        && !empty($this->paymentToken)
        && $this->instantPurchaseOption
        && $this->shippingAddress
        && $this->billingAddress
        && $this->shippingMethod;
    }
}
