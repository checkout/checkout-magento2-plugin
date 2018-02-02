<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Resource;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Address;
use Magento\Checkout\Model\Type\Onepage;
use CheckoutCom\Magento2\Model\Service\OrderService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Observer\DataAssignObserver;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Api\MobilePaymentInterface;

/**
 * Defines the implementaton class of the charge through API.
 */
class MobilePayment implements MobilePaymentInterface
{
    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var CustomerRepositoryInterface
     */        
    protected $customerRepository;

    /**
     * @var Product
     */
    protected $productManager;

    /**
     * @var Array
     */    
    protected $data;

    /**
     * @var String
     */    
    protected $customer;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var Address
     */
    protected $addressManager;

    /**
     * MobilePayment Model constructor.
     * @param GatewayConfig $gatewayConfig
     * @param TransferFactory $transferFactory
    */
    public function __construct(GatewayConfig $gatewayConfig, TransferFactory $transferFactory, CustomerRepositoryInterface $customerRepository, Product $productManager, QuoteFactory $quoteFactory, StoreManagerInterface $storeManager, OrderService $orderService, Address $addressManager) {
        $this->gatewayConfig    = $gatewayConfig;
        $this->transferFactory  = $transferFactory;
        $this->customerRepository  = $customerRepository;
        $this->productManager  = $productManager;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->orderService     = $orderService;
        $this->addressManager = $addressManager;
    }

    /**
     * Perfom a charge given the required parameters.
     *
     * @api
     * @param mixed $data.
     * @return int.
     */
    public function charge($data) {

        // JSON post data to object
        $this->data = json_decode($data);

        // Load the customer from email
        $this->customer = $this->customerRepository->get(filter_var($this->data->email, FILTER_SANITIZE_EMAIL));    
        // If customer exists and amount is valid
        if ( (int) $this->customer->getId() > 0 && (float) $this->data->value > 0)  {

            // Prepare the product list
            if ( isset($this->data->products) && is_array($this->data->products) && count($this->data->products) > 0 )  {

                // Submit request
                return (int) $this->submitRequest();
            }
        }

        return false;
    }

    private function submitRequest() {

        //init the quote
        $quote = $this->quoteFactory->create();
        $quote->setStore($this->storeManager->getStore());

        // Assign customer and currency
        $quote->setCurrency();
        $quote->assignCustomer($this->customer);
 
        // Prepare the products for shop order submission
        $quote = $this->prepareProducts($quote);

        // Set the billing address
        $billingID = $this->customer->getDefaultBilling();
        $billingAddress = $this->addressManager->load($billingID);
        $quote->getBillingAddress()->addData($billingAddress->getData());

        // Set the shipping address
        $shippingID = $this->customer->getDefaultShipping();
        $shippingAddress = $this->addressManager->load($shippingID);
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->addData($shippingAddress->getData());
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        // Inventory
        $quote->setInventoryProcessed(false);

        // Set payment
        $payment = $quote->getPayment();
        $payment->setMethod(ConfigProvider::CODE);
        $payment->setAdditionalInformation(DataAssignObserver::CARD_TOKEN_ID, $this->data->cardToken);
        $quote->save();

        // Only registered users can order
        $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);

        // Set sales order payment
        $quote->getPayment()->importData(['method' => ConfigProvider::CODE]);

        // Save the quote
        $quote->collectTotals()->save();

        // Place the order
        return $this->orderService->execute($quote, $this->data->cardToken, $agreement = []);
    }

    private function prepareProducts(Quote $quote) {

        // Loop through the products array
        foreach ($this->data->products as $product) {

            // If the product id > 0
            if ((int) $product->id > 0) {

                // Load the product object
                $p = $this->productManager->load($product->id);

                // Add to quote
                $quote->addProduct($p, intval($product->quantity));
            }
        }

        // Return the quote
        return $quote;
    }

    private function prepareProductsForGateway() {
        // Prepare the output array
        $output = array();

        // Loop through the products array
        foreach ($this->data->products as $product) {

            // If the product id is valid
            if ((int) $product->id > 0) {
                // Load the product object
                $p = $this->productManager->load($product->id);

                // Feed the output array
                $output[] = array(
                    'name' => $p->getName(),
                    'price' => number_format($p->getPrice(), 2),
                    'quantity' => (int) $product->quantity,
                );
            }
        }

        // Return the array
        return $output;
    }
}