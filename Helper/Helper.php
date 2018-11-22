<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Helper;

use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\File\Csv;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;

class Helper {

    const EMAIL_COOKIE_NAME = 'ckoEmail';

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Csv
     */
    protected $csvParser;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    public function __construct(
        Reader $directoryReader,
        Csv $csvParser,
        Config $config,
        CookieManagerInterface $cookieManager,
        CustomerSession $customerSession
    ) {
        $this->directoryReader = $directoryReader;
        $this->csvParser       = $csvParser;
        $this->config          = $config;
        $this->cookieManager   = $cookieManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Get the module version from composer.json file
     */    
    public function getModuleVersion() {
        // Get the module path
        $module_path = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2');

        // Get the content of composer.json
        $json = file_get_contents($module_path . '/composer.json');

        // Decode the data and return
        $data = json_decode($json);

        return $data->version;
    }

    /**
     * Checks the MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin) {
        // Set the root path
        $csvPath = $this->directoryReader->getModuleDir('', 'CheckoutCom_Magento2')  . '/' . $this->config->getMadaBinsPath();

        // Get the data
        $csvData = $this->csvParser->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the MADA BIN array
        $binArray = [];
        foreach ($csvData as $row) {
            $binArray[] = $row[1];
        }

        return in_array($bin, $binArray);
    }

    /**
     * Sets the email for guest users
     *
     * @return bool
     */
    public function prepareGuestQuote($quote, $email = null) {
        // Retrieve the user email 
        $guestEmail = $email
        ?? $this->customerSession->getData('checkoutSessionData')['customerEmail']
        ?? $quote->getCustomerEmail() 
        ?? $quote->getBillingAddress()->getEmail()
        ?? $this->cookieManager->getCookie(self::EMAIL_COOKIE_NAME);

        // Set the quote as guest
        $quote->setCustomerId(null)
        ->setCustomerEmail($guestEmail)
        ->setCustomerIsGuest(true)
        ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

        // Delete the cookie
        $this->cookieManager->deleteCookie(self::EMAIL_COOKIE_NAME);

        return $quote;
    }
}
