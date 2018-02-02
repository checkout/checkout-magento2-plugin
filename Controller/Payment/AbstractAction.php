<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use InvalidArgumentException;

abstract class AbstractAction extends Action {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(Context $context, GatewayConfig $gatewayConfig) {
        parent::__construct($context);

        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Makes redirection if the gateway is not activated.
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface|Redirect
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request) {

        return parent::dispatch($request);
    }

    /**
     * Returns created results redirect instance.
     *
     * @return \Magento\Framework\Controller\ResultInterface|Redirect
     * @throws InvalidArgumentException
     */
    protected function getResultRedirect() {
        /* @var $resultRedirect Redirect */
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
    }

    /**
     * Throws exception if the given quota is not valid.
     *
     * @param CartInterface $quote
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateQuote($quote) {
        if (!$quote || !$quote->getItemsCount()) {
            throw new InvalidArgumentException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * Assigns the given email to the provided quote instance.
     *
     * @param Quote $quote
     * @param $email
     */
    protected function assignGuestEmail(Quote $quote, $email) {
        $quote->setCustomerEmail($email);
        $quote->getCustomer()->setEmail($email);
        $quote->getBillingAddress()->setEmail($email);
    }

}
