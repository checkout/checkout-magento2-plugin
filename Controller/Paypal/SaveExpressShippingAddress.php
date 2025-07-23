<?php

declare(strict_types=1);
/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Paypal;

use Exception;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class SaveExpressShippingAddress
 */
class SaveExpressShippingAddress extends SaveData implements HttpPostActionInterface
{
    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        $quote = $this->checkoutSession->getQuote();

        $redirectToCart = $this->shouldRedirectToCart();

        if ($redirectToCart) {
            return $this->redirectFactory->create()->setUrl($this->urlInterface->getUrl('checkout/cart'));
        }

        //Assign address and save quote
        try {
            $quoteAddress = $this->buildAddress();
            $quote->setBillingAddress($quoteAddress)->setShippingAddress($quoteAddress);
            $this->cartRepository->save($quote);

            // Auto assign shipping method
            $shippingAddress = $quote->getShippingAddress();
            if ($this->paypalMethod->getConfigData('express_auto_method') && !$shippingAddress->getShippingMethod()) {
                $shippingAddress->collectShippingRates();
                $rates = $shippingAddress->getGroupedAllShippingRates();
                if (!empty($rates)) {
                    foreach ($rates as $carrier) {
                        foreach ($carrier as $carrierMethod) {
                            $this->setShippingMethod($carrierMethod->getCode(), $quote);
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->write(__('Error occured while saving paypal express shipping address: %1', $e->getMessage()));
            $this->messageManager->addErrorMessage(__('Unable to save Addres'));
        }

        return $this->redirectFactory->create()->setRefererUrl();
    }

    public function buildAddress(): AddressInterface
    {
        $address = $this->addressInterfaceFactory->create();
        $address->setFirstname($this->request->getParam(CustomerAddressInterface::FIRSTNAME));
        $address->setLastName($this->request->getParam(CustomerAddressInterface::LASTNAME));
        $address->setCompany($this->request->getParam(CustomerAddressInterface::COMPANY));
        $address->setCountryId($this->request->getParam(CustomerAddressInterface::COUNTRY_ID));
        $address->setPostCode($this->request->getParam(CustomerAddressInterface::POSTCODE));
        $address->setRegionId($this->request->getParam(CustomerAddressInterface::REGION_ID));
        $address->setCity($this->request->getParam(CustomerAddressInterface::CITY));
        $address->setStreet($this->request->getParam(CustomerAddressInterface::STREET));
        $address->setTelephone($this->request->getParam(CustomerAddressInterface::TELEPHONE));

        return $address;
    }
}
