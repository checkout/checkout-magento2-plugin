<?php
namespace CheckoutCom\Magento2\Controller\Adminhtml\Plan;
 
use Magento\Backend\App\Action;

class Save extends Action
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        // Get the data from request
        $data = $this->getRequest()->getPostValue();

        // Check if the data is valid
        if (!$data) {
            $this->_redirect('checkout_com/plan/addrow');
            return;
        }

        // Set the required dates
        $data['created_at'] = empty($data['created_at']) ? time() : $data['created_at'];
        $data['updated_at'] = time();

        // Attept a save operation
        try {
            $rowData = $this->_objectManager->create('CheckoutCom\Magento2\Model\Plan');
            $rowData->setData($data);
            if (isset($data['id'])) {
                $rowData->setEntityId($data['id']);
            }
            $rowData->save();
            $this->messageManager->addSuccess(__('The item has been successfully saved.'));
        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('checkout_com/plan/index');
    }
 
    /**
     * Check Category Map permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CheckoutCom_Magento2::add_row');
    }
}