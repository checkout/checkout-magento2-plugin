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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Config\Backend\Source\ConfigLanguageFallback;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ShopperHandlerService
 */
class ShopperHandlerService
{
    private Config $config;
    private ConfigLanguageFallback $languageCallbackConfig;
    private Session $customerSession;
    private CustomerRepositoryInterface $customerRepository;
    private Resolver $localeResolver;

    public function __construct(
        Config $config,
        ConfigLanguageFallback $languageCallbackConfig,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        Resolver $localeResolver
    ) {
        $this->config = $config;
        $this->languageCallbackConfig = $languageCallbackConfig;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Description getCustomerData function
     *
     * @param array $filters
     *
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerData(array $filters = []): CustomerInterface
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
     * Retrieves the customer language
     *
     * @param string $default
     *
     * @return string
     */
    public function getCustomerLocale(string $default = 'en_GB'): string
    {
        $locale = $this->localeResolver->getLocale();
        if (!$locale) {
            return $default;
        }

        return $locale;
    }

    /**
     * Retrieves the customer language fallback for the card payments form
     *
     * @param string $default
     *
     * @return mixed|string
     */
    public function getLanguageFallback(string $default = 'en_GB')
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
