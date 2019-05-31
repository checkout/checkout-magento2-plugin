<?php

namespace CheckoutCom\Magento2\Model\Service;

class ShopperHandlerService
{

    /**
     * Default locale code.
     *
     * @var        string
     */
    const DEFAULT_LOCALE = 'en_US';

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Resolver
     */
    protected $localeResolver;

    /**
     * ShopperHandlerService constructor
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Locale\Resolver $localeResolver
    )
    {
        $this->localeResolver  = $localeResolver;
        $this->customerSession = $customerSession;
        $this->customerRepository  = $customerRepository;
    }

    public function getCustomerData($filters = []) {
        if (isset($filters['id'])) {
            return $this->customerRepository->getById($filters['id']);
        }
        else if (isset($filters['email'])) {
            return $this->customerRepository->get(
                filter_var($filters['email'],
                FILTER_SANITIZE_EMAIL)
            );
        }
        else {
            $customerId = $this->customerSession->getCustomer()->getId();
            return $this->customerRepository->getById($customerId);
        }
    }

    /**
     * Retrieves the customer language.
     */
    public function getCustomerLocale($dft = 'en_GB')
    {
        $locale = $this->localeResolver->getLocale();
        if(!$locale) {
            $locale = $dft;
        }

        return $locale;
    }
}