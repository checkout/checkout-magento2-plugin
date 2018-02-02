<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Adminhtml\Subscription;
 
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action;
use CheckoutCom\Magento2\Model\ResourceModel\Subscription\CollectionFactory;
use CheckoutCom\Magento2\Model\Subscription;
use CheckoutCom\Magento2\Model\Service\SubscriptionService;

class MassDelete extends Action
{
    /**
     * Massactions filter.
     *
     * @var Filter
     */
    protected $_filter;
 
    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var Subscription
     */
    protected $model;

    /**
     * @var SubscriptionService
     */
    protected $objectService;

    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param Collection $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Subscription $model,
        SubscriptionService $objectService
    ) 
    {
        parent::__construct($context);
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->model = $model;
        $this->objectService = $objectService;
    }
 
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // Load the collection
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $recordDeleted = 0;

        // Loop through the items
        foreach ($collection->getItems() as $item) {  
            // Delete in database
            $this->model->load($item->getId())->delete();
            $recordDeleted++;

            // Delete in the Hub
            $this->objectService->cancel($data);
        }

        // Add meessage
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $recordDeleted)
        );
 
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
 
    /**
     * Check delete Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CheckoutCom_Magento2::user_subscriptions_list');
    }
}