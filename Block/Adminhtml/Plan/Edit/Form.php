<?php
namespace CheckoutCom\Magento2\Block\Adminhtml\Plan\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
use CheckoutCom\Magento2\Model\Fields\PlanStatus;
use CheckoutCom\Magento2\Model\Fields\Currency;

/**
 * Adminhtml Add New Row Form.
 */
class Form extends Generic 
{

    protected $_systemStore;
    protected $_statusOptions;
    protected $_currencyOptions;
    protected $_wysiwygConfig;
 
    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Config $wysiwygConfig, PlanStatus $statusOptions, Currency $currencyOptions, array $data = []) 
    {
        $this->_statusOptions = $statusOptions;
        $this->_currencyOptions = $currencyOptions;
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
 
        // Prepare CSS
        $fieldsCss = 'width:400px;';
        $readOnlyCss = 'color:#777777;background-color:#dddddd;';

        // HTML prefix
        $form->setHtmlIdPrefix('ckom2_grid_');

        // Build the form
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Editing %1', $model->getName()), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Adding Payment Plan'), 'class' => 'fieldset-wide']
            );
        }
 
        $fieldset->addField(
            'plan_name',
            'text',
            [
                'name' => 'plan_name',
                'label' => __('Name'),
                'id' => 'title',
                'title' => __('Name'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );
  
        $fieldset->addField(
            'track_id',
            'text',
            [
                'name' => 'track_id',
                'label' => __('Track ID'),
                'id' => 'track_id',
                'title' => __('Track ID'),
                'class' => 'required-entry',
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'required' => true,
                'style' => $fieldsCss . $readOnlyCss,
            ]
        );

        $fieldset->addField(
            'auto_cap_time',
            'text',
            [
                'name' => 'auto_cap_time',
                'label' => __('Auto capture time'),
                'id' => 'auto_cap_time',
                'title' => __('Auto capture time'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'plan_value',
            'text',
            [
                'name' => 'plan_value',
                'label' => __('Value'),
                'id' => 'plan_value',
                'title' => __('Value'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
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
                'values' => $this->_currencyOptions->getOptionArray(),
                'class' => 'status',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'cycle',
            'text',
            [
                'name' => 'cycle',
                'label' => __('Cycle'),
                'id' => 'cycle',
                'title' => __('Cycle'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'recurring_count',
            'text',
            [
                'name' => 'recurring_count',
                'label' => __('Recurring count'),
                'id' => 'recurring_count',
                'title' => __('Recurring count'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'plan_status',
            'select',
            [
                'name' => 'plan_status',
                'label' => __('Status'),
                'id' => 'plan_status',
                'title' => __('Status'),
                'values' => $this->_statusOptions->getOptionArray(),
                'class' => 'status',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'created_at',
            'text',
            [
                'name' => 'created_at',
                'label' => __('Created'),
                'id' => 'recurring_count',
                'title' => __('Created'),
                'class' => 'required-entry',
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'required' => true,
                'style' => $fieldsCss . $readOnlyCss,
            ]
        );

        $fieldset->addField(
            'updated_at',
            'text',
            [
                'name' => 'updated_at',
                'label' => __('Updated'),
                'id' => 'updated_at',
                'title' => __('Updated'),
                'class' => 'required-entry',
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'required' => true,
                'style' => $fieldsCss . $readOnlyCss,
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}