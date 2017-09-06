<?php
namespace CheckoutCom\Magento2\Block\Adminhtml\Plan\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
use CheckoutCom\Magento2\Model\Fields\Status;

/**
 * Adminhtml Add New Row Form.
 */
class Form extends Generic 
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
 
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Config $wysiwygConfig , Status $options,
        array $data = []
    ) 
    {
        $this->_options = $options;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }
 
    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            ['data' => [
                            'id' => 'edit_form', 
                            'enctype' => 'multipart/form-data', 
                            'action' => $this->getData('action'), 
                            'method' => 'post'
                        ]
            ]
        );
 
        $form->setHtmlIdPrefix('ckom2_grid_');
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Edit Item Data'), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Add Item Data'), 'class' => 'fieldset-wide']
            );
        }
 
        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'plan_name',
                'label' => __('Name'),
                'id' => 'title',
                'title' => __('Name'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );
 
        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);
 
        $fieldset->addField(
            'track_id',
            'text',
            [
                'name' => 'track_id',
                'label' => __('Track ID'),
                'id' => 'track_id',
                'title' => __('Track ID'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'currency',
            'select',
            [
                'name' => 'currency',
                'label' => __('Currency'),
                'id' => 'currency',
                'title' => __('Currency'),
                'values' => $this->_options->getOptionArray(),
                'class' => 'status',
                'required' => true,
            ]
        );
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}