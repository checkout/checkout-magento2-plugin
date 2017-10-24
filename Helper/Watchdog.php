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

use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Sales\Model\Order;
use Magento\Paypal\Model\Info;
use Magento\Sales\Model\Order\Status as OrderStatus;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;

class Watchdog {

    protected $messageManager;
    protected $config;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory
     */
    private $statusCollectionFactory;

    public function __construct(ManagerInterface $messageManager, Config $config, StatusCollectionFactory $statusCollectionFactory) {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function bark($data) {
        if ($this->config->isDebugMode()) {
            // Add the response code
            if (isset($data['responseCode'])) {
                $this->messageManager->addNoticeMessage(__('Response code') . ' : ' .  $data['responseCode']);
            }

            // Add the response message
            if (isset($data['responseMessage'])) {
                $this->messageManager->addNoticeMessage(__('Response message') . ' : ' .  $data['responseMessage']);    
            }   

            // Add the error code
            if (isset($data['errorCode'])) {
                $this->messageManager->addNoticeMessage(__('Error code') . ' : ' .  $data['errorCode']);    
            }  

            // Add the error code
            if (isset($data['status'])) {
                $this->messageManager->addNoticeMessage(__('Status') . ' : ' .  $data['status']);    
            }   

            // Add the message
            if (isset($data['message'])) {
                $this->messageManager->addNoticeMessage(__('Message') . ' : ' .  $data['message']);    
            }
        }
    }

    /**
     * Sets Order's Status or loads from config set one, based on it loads appropriate State
     * If comment and notification flag are passed then Status History is set to given values
     *
     * @param Order $order
     * @param null|string $status
     * @param null|string $historyComment
     * @param bool $isCustomerNotified
     *
     * @return $this
     */
    public function updateOrderStatus(Order $order, $status = null, $historyComment = null, $isCustomerNotified = true)
    {
        if (empty($status)) {
            $status = Info::PAYMENTSTATUS_PENDING;
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollection */
        $statusCollection = $this->statusCollectionFactory->create()->joinStates();

        /** @var OrderStatus $statusItem */
        foreach ($statusCollection as $statusItem) {
            if ($statusItem->getStatus() == $status) {
                $order->setState($statusItem->getState());
                break;
            }
        }

        if (empty($historyComment) === false) {
            $order->addStatusToHistory($status, $historyComment, $isCustomerNotified);
        }

        return $this;
    }
}
