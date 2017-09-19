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
use Magento\Backend\App\Action\Context;
use CheckoutCom\Magento2\Model\Service\PaymentPlanService;

class Save extends Action
{
    const EDIT_URL = 'checkout_com/plan/addrow';

    const LIST_URL = 'checkout_com/plan/index';

    const MODEL_CLASS = '\CheckoutCom\Magento2\Model\Plan';

    protected $objectService;

    protected $isNew;
    
    public function __construct(Context $context, PaymentPlanService $objectService) {
        parent::__construct($context);  
        $this->objectService = $objectService;
    }

    public function execute()
    {
        // Get the data from request
        $data = $this->getRequest()->getPostValue();

        // Set the isNew flag
        $this->isNew = isset($data['id']);

        // Check if the data is valid
        if (!$data) {
            $this->_redirect(self::EDIT_URL);
            return;
        }

        // Attempt a save operation
        try {
            // Update dat
            $data = $this->_prepareData($data);

            // Create the item in the hub
            if ($this->isNew) {
                $this->objectService->create($data);
            }
            else {
                $this->objectService->update($data);
            }

            // Save the row data
            $this->_saveRowData($data);

        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        // Redirect to the grid list page
        $this->_redirect(self::LIST_URL);
    }

    protected function _prepareData($formData)
    {
        // If the plan is not new
        if (!$this->isNew) {
            // Create the track id
            $formData['track_id'] = $this->_createTrackId();

            // Set the required dates
            $formData['created_at'] = empty($formData['created_at']) ? time() : $formData['created_at'];
            $formData['updated_at'] = time();
        }

        return $formData;
    }

    protected function _saveRowData($data)
    {
        // Create the data object
        $rowData = $this->_objectManager->create(self::MODEL_CLASS);
        $rowData->setData($data);

        // Set the object entity id
        if ($this->isNew) {
            $rowData->setEntityId($data['id']);
        }

        // Save the plan
        $rowData->save();
    }

    /**
     * Generate a track id.
     *
     * @return string
     */
    protected function _createTrackId()
    {
        return time();
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