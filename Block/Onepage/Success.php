<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CheckoutCom\Magento2\Block\Onepage;

use \Magento\Framework\View\Element\Template;

class Success extends Template
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getRealOrderId()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create()->load($this->getLastOrderId());
        return $order->getIncrementId();
    }

    /**
     * 
     */
    public function getKnetInfo()
    {
        return "test";
    }
}
