<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Model\InstantPurchase;

class CustomerData implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var InstantPurchaseModel
     */
    private $instantPurchase;

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
     * @var PaymentTokenFormatter
     */
    private $paymentTokenFormatter;

    /**
     * InstantPurchase constructor.
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param InstantPurchaseModel $instantPurchase
     * @param CustomerAddressesFormatter $customerAddressesFormatter
     * @param ShippingMethodFormatter $shippingMethodFormatter
     * @param VaultHandlerService $vaultHandler
     * @param PaymentTokenFormatter $paymentTokenFormatter
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\InstantPurchase\Model\InstantPurchaseInterface $instantPurchase,
        \CheckoutCom\Magento2\Model\InstantPurchase $paymentTokenFormatter,
        \Magento\InstantPurchase\Model\Ui\CustomerAddressesFormatter $customerAddressesFormatter,
        \Magento\InstantPurchase\Model\Ui\ShippingMethodFormatter $shippingMethodFormatter,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->instantPurchase = $instantPurchase;
        $this->customerAddressesFormatter = $customerAddressesFormatter;
        $this->shippingMethodFormatter = $shippingMethodFormatter;
        $this->vaultHandler = $vaultHandler;
        $this->paymentTokenFormatter = $paymentTokenFormatter;

        // Prepare the required data
        $this->prepareData();
    }

    /**
     * Prepare the data needed for instant purchase
     */  
    protected function prepareData() {
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
    }

    /**
     * Load the instant purchase option
     */  
    protected function loadOption() {
        // Get the store manager
        $store = $this->storeManager->getStore();       

        // Get the customer instance
        $customer = $this->customerSession->getCustomer();

        // Return the option
        return $this->instantPurchase->getOption($store, $customer);
    }

    /**
     * @inheritdoc
     */
    public function getSectionData(): array
    {
        // Set the instant purchase availability
        $isAvailable = $this->isAvailable();
        $data = ['available' => $isAvailable];
        if (!$isAvailable) return $data;

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

        return $data;
    }

    /**
     * Checks if the instant purchase option is available
     */    
    protected function isAvailable() {
        return $this->customerSession->isLoggedIn()
        && !empty($this->paymentToken)
        && $this->instantPurchaseOption
        && $this->shippingAddress
        && $this->billingAddress
        && $this->shippingMethod;
    }
}