<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Controller\Adminhtml\Plan;
 
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class AddRow extends Action
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry    $coreRegistry
     */
    public function __construct(Context $context, Registry $coreRegistry) 
    {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
    }
    /**
     * Add New Row Form page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $rowId = (int) $this->getRequest()->getParam('id');
        $rowData = $this->_objectManager->create('CheckoutCom\Magento2\Model\Plan');
        if ($rowId) {
            $rowData = $rowData->load($rowId);
            $rowTitle = $rowData->getTitle();
            if (!$rowData->getEntityId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->_redirect('checkout_com/plan/rowdata');
                return;
            }
        }
 
        $this->_coreRegistry->register('row_data', $rowData);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = $rowId ? __('Edit Item').$rowTitle : __('Add New Item');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CheckoutCom_Magento2::add_row');
    }
}