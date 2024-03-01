<?php

declare(strict_types=1);

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

namespace CheckoutCom\Magento2\Block\Paypal\Review;

use CheckoutCom\Magento2\Controller\Paypal\Review;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Block\Address\Edit as CustomerAddressEdit;
use Magento\Customer\Helper\Address;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;

class ShippingAddress extends CustomerAddressEdit
{
    protected CheckoutSession $checkoutSession;
    protected AddressConfig $addressConfig;
    protected CustomerInterfaceFactory $customerInterfaceFactory;
    protected UrlInterface $url;
    protected RequestInterface $request;

    public function __construct(
        Context $context,
        Data $directoryHelper,
        EncoderInterface $jsonEncoder,
        Config $configCacheType,
        CollectionFactory $regionCollectionFactory,
        CountryCollectionFactory $countryCollectionFactory,
        Session $customerSession,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        CurrentCustomer $currentCustomer,
        DataObjectHelper $dataObjectHelper,
        CheckoutSession $checkoutSession,
        AddressConfig $addressConfig,
        CustomerInterfaceFactory $customerInterfaceFactory,
        UrlInterface $url,
        RequestInterface $request,
        array $data = [],
        AddressMetadataInterface $addressMetadata = null,
        Address $addressHelper = null
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data,
            $addressMetadata,
            $addressHelper
        );

        $this->addressConfig = $addressConfig;
        $this->checkoutSession = $checkoutSession;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->url = $url;
        $this->request = $request;

        $this->_address = $this->checkoutSession->getQuote()->getShippingAddress();
    }

    public function getAddress()
    {
        return $this->checkoutSession->getQuote()->getShippingAddress();
    }

    public function getCustomer()
    {
        return $this->customerInterfaceFactory->create();
    }

    public function isDefaultBilling()
    {
        return false;
    }

    public function isDefaultShipping()
    {
        return false;
    }

    public function canSetAsDefaultBilling()
    {
        return false;
    }

    public function canSetAsDefaultShipping()
    {
        return false;
    }

    public function getCustomerAddressCount()
    {
        return 1;
    }

    public function getRegion()
    {
        return (string)$this->getAddress()->getRegion();
    }

    public function getRegionId()
    {
        return $this->getAddress()->getRegionId() ?? 0;
    }

    public function getTitle(): Phrase
    {
        return __('Review Order');
    }

    public function getSaveUrl()
    {
        return $this->url->getUrl('checkoutcom/paypal/saveExpressShippingAddress', [
            Review::PAYMENT_CONTEXT_ID_PARAMETER => $this->request->getParam(Review::PAYMENT_CONTEXT_ID_PARAMETER)
        ]);
    }
}
