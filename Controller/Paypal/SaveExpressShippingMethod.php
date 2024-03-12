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
namespace CheckoutCom\Magento2\Controller\Paypal;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class SaveExpressShippingMethod extends SaveData implements HttpGetActionInterface
{
    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        $quote = $this->checkoutSession->getQuote();

        $redirectToCart = $this->shouldRedirectToCart();
        $methodCode = $this->request->getParam(Review::SHIPPING_METHOD_PARAMETER);
        if (!$redirectToCart && !$methodCode) {
            $this->messageManager->addErrorMessage(__('Missing shipping method'));

            return $this->redirectFactory->create()->setRefererUrl();
        }

        if ($redirectToCart) {
            return $this->redirectFactory->create()->setUrl($this->urlInterface->getUrl('checkout/cart'));
        }

        // Save Shipping address
        try {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
            $cartExtension = $quote->getExtensionAttributes();
            if ($cartExtension && $cartExtension->getShippingAssignments()) {
                $cartExtension->getShippingAssignments()[0]
                    ->getShipping()
                    ->setMethod($methodCode);
            }
            $quote->collectTotals();

            $this->cartRepository->save($quote);
        } catch (Exception $e) {
            $this->logger->write(__('Error occured while saving paypal express shipping method: %1', $e->getMessage()));
            $this->messageManager->addErrorMessage(__('An error occurred while saving shipping method'));
        }

        return $this->redirectFactory->create()->setRefererUrl();
    }
}
