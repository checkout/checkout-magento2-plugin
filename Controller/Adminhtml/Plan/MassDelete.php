<?php
namespace CheckoutCom\Magento2\Controller\Adminhtml\Plan;
 
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action;
use CheckoutCom\Magento2\Model\ResourceModel\Plan\CollectionFactory;
use CheckoutCom\Magento2\Model\Plan;

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
     * @var Plan
     */
    protected $model;
 
    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param Collection $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Plan $model
    ) 
    {
        parent::__construct($context);
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->model = $model;
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
            $this->model->load($item->getId())->delete();
            $recordDeleted++;
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
        return $this->_authorization->isAllowed('CheckoutCom_Magento2::payment_plans_list');
    }
}