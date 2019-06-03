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
     * @var Logger
     */
    protected $logger;

    /**
     * ShopperHandlerService constructor
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \CheckoutCom\Magento2\Helper\Logger $logger
    )
    {
        $this->localeResolver  = $localeResolver;
        $this->customerSession = $customerSession;
        $this->customerRepository  = $customerRepository;
        $this->logger = $logger;
    }

    public function getCustomerData($filters = []) {
        try {
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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves the customer language.
     */
    public function getCustomerLocale($default = 'en_GB')
    {
        try {
            $locale = $this->localeResolver->getLocale();
            if (!$locale) {
                return $default;
            }

            return $locale;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return $default;
        }
    }
}