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

namespace CheckoutCom\Magento2\Model\Service;

/**
 * Class ShopperHandlerService.
 */
class ShopperHandlerService
{
    /**
     * @var Config
     */
    public $config;

    /**
     * @var ConfigLanguageFallback
     */
    public $languageCallbackConfig;
    
    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    public $customerRepository;

    /**
     * @var Resolver
     */
    public $localeResolver;

    /**
     * ShopperHandlerService constructor
     */
    public function __construct(
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigLanguageFallback $languageCallbackConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        $this->config = $config;
        $this->languageCallbackConfig = $languageCallbackConfig;
        $this->customerSession = $customerSession;
        $this->customerRepository  = $customerRepository;
        $this->localeResolver  = $localeResolver;
    }

    public function getCustomerData($filters = [])
    {
        if (isset($filters['id'])) {
            return $this->customerRepository->getById($filters['id']);
        } elseif (isset($filters['email'])) {
            return $this->customerRepository->get(
                filter_var(
                    $filters['email'],
                    FILTER_SANITIZE_EMAIL
                )
            );
        } else {
            $customerId = $this->customerSession->getCustomer()->getId();
            return $this->customerRepository->getById($customerId);
        }
    }

    /**
     * Retrieves the customer language.
     */
    public function getCustomerLocale($default = 'en_GB')
    {
        $locale = $this->localeResolver->getLocale();
        if (!$locale) {
            return $default;
        }

        return $locale;
    }

    /**
     * Retrieves the customer language fallback for the card payments form.
     */
    public function getLanguageFallback($default = 'en_GB')
    {
        // Get and format customer locale
        $customerLocale = strtoupper($this->getCustomerLocale());

        // Return the customer locale is available
        $availableLanguages = $this->languageCallbackConfig->toOptionArray();
        foreach ($availableLanguages as $lg) {
            if ($lg['value'] == $customerLocale) {
                return $customerLocale;
            }
        }

        // Language fallback
        return $this->config->getValue(
            'language_fallback',
            'checkoutcom_card_payment'
        );
    }
}
