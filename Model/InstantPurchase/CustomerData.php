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

namespace CheckoutCom\Magento2\Model\InstantPurchase;

use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InstantPurchase\Model\InstantPurchaseInterface;
use Magento\InstantPurchase\Model\InstantPurchaseOption;
use Magento\InstantPurchase\Model\Ui\CustomerAddressesFormatter;
use Magento\InstantPurchase\Model\Ui\PaymentTokenFormatter;
use Magento\InstantPurchase\Model\Ui\ShippingMethodFormatter;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CustomerData
 */
class CustomerData implements SectionSourceInterface
{
    /**
     * $storeManager field
     *
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    /**
     * $instantPurchase field
     *
     * @var InstantPurchaseInterface $instantPurchase
     */
    private $instantPurchase;
    /**
     * $customerSession field
     *
     * @var Session $customerSession
     */
    private $customerSession;
    /**
     * $customerAddressesFormatter field
     *
     * @var CustomerAddressesFormatter $customerAddressesFormatter
     */
    private $customerAddressesFormatter;
    /**
     * $shippingMethodFormatter field
     *
     * @var ShippingMethodFormatter $shippingMethodFormatter
     */
    private $shippingMethodFormatter;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;
    /**
     * $availabilityChecker field
     *
     * @var AvailabilityChecker $availabilityChecker
     */
    private $availabilityChecker;
    /**
     * $paymentTokenFormatter field
     *
     * @var PaymentTokenFormatter $paymentTokenFormatter
     */
    private $paymentTokenFormatter;
    /**
     * $paymentToken field
     *
     * @var array|mixed $paymentToken
     */
    private $paymentToken;
    /**
     * $instantPurchaseOption field
     *
     * @var InstantPurchaseOption $instantPurchaseOption
     */
    private $instantPurchaseOption;
    /**
     * $shippingAddress field
     *
     * @var Address $shippingAddress
     */
    private $shippingAddress;
    /**
     * $billingAddress field
     *
     * @var Address $billingAddress
     */
    private $billingAddress;
    /**
     * $shippingMethod field
     *
     * @var ShippingMethodInterface $shippingMethod
     */
    private $shippingMethod;

    /**
     * CustomerData constructor
     *
     * @param StoreManagerInterface      $storeManager
     * @param InstantPurchaseInterface   $instantPurchase
     * @param Session                    $customerSession
     * @param TokenFormatter             $paymentTokenFormatter
     * @param CustomerAddressesFormatter $customerAddressesFormatter
     * @param ShippingMethodFormatter    $shippingMethodFormatter
     * @param VaultHandlerService        $vaultHandler
     * @param AvailabilityChecker        $availabilityChecker
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        InstantPurchaseInterface $instantPurchase,
        Session $customerSession,
        TokenFormatter $paymentTokenFormatter,
        CustomerAddressesFormatter $customerAddressesFormatter,
        ShippingMethodFormatter $shippingMethodFormatter,
        VaultHandlerService $vaultHandler,
        AvailabilityChecker $availabilityChecker
    ) {
        $this->storeManager               = $storeManager;
        $this->instantPurchase            = $instantPurchase;
        $this->customerSession            = $customerSession;
        $this->customerAddressesFormatter = $customerAddressesFormatter;
        $this->shippingMethodFormatter    = $shippingMethodFormatter;
        $this->vaultHandler               = $vaultHandler;
        $this->availabilityChecker        = $availabilityChecker;
        $this->paymentTokenFormatter      = $paymentTokenFormatter;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed[]|mixed[][]
     * @throws LocalizedException
     */
    public function getSectionData(): array
    {
        // Set the instant purchase availability
        $data = ['available' => $this->isAvailable()];
        if (!$this->isAvailable()) {
            return $data;
        }

        // Prepare the required data
        $this->prepareData();

        // Check if the option can be displayed
        if (!$this->canDisplay()) {
            return $data;
        }

        // Build the instant purchase data
        $data += [
            'paymentToken'    => [
                'publicHash' => $this->paymentToken->getPublicHash(),
                'summary'    => $this->paymentTokenFormatter->formatPaymentToken($this->paymentToken),
            ],
            'shippingAddress' => [
                'id'      => $this->shippingAddress->getId(),
                'summary' => $this->customerAddressesFormatter->format($this->shippingAddress),
            ],
            'billingAddress'  => [
                'id'      => $this->billingAddress->getId(),
                'summary' => $this->customerAddressesFormatter->format($this->billingAddress),
            ],
            'shippingMethod'  => [
                'carrier' => $this->shippingMethod->getCarrierCode(),
                'method'  => $this->shippingMethod->getMethodCode(),
                'summary' => $this->shippingMethodFormatter->format($this->shippingMethod),
            ],
        ];

        return $data;
    }

    /**
     * Prepare the data needed for instant purchase
     *
     * @return void
     * @throws LocalizedException
     */
    public function prepareData(): void
    {
        // Get the  payment token
        $this->paymentToken = $this->vaultHandler->getLastSavedCard();

        // Get the instant purchase option
        $this->instantPurchaseOption = $this->loadOption();

        // Get the shipping and billing data
        if ($this->instantPurchaseOption) {
            $this->shippingAddress = $this->instantPurchaseOption->getShippingAddress();
            $this->billingAddress  = $this->instantPurchaseOption->getBillingAddress();
            $this->shippingMethod  = $this->instantPurchaseOption->getShippingMethod();
        }
    }

    /**
     * Load the instant purchase option
     *
     * @return InstantPurchaseOption
     * @throws NoSuchEntityException
     */
    public function loadOption(): InstantPurchaseOption
    {
        return $this->instantPurchase->getOption(
            $this->storeManager->getStore(),
            $this->customerSession->getCustomer()
        );
    }

    /**
     * Checks if the instant purchase option is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->availabilityChecker->isAvailable();
    }

    /**
     * Checks if the instant purchase option can be displayed
     *
     * @return bool
     */
    protected function canDisplay(): bool
    {
        return $this->customerSession->isLoggedIn(
            ) && !empty($this->paymentToken) && $this->instantPurchaseOption && $this->shippingAddress && $this->billingAddress && $this->shippingMethod;
    }
}
