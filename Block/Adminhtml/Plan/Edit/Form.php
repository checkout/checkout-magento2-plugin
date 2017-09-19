<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
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

    protected function _prepareLayout()
    {
        // Delete button
        $this->getToolbar()->addChild(
            'delete',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Delete'),
                'title' => __('Delete'),
                'onclick' => 'deleteConfirm(' . json_encode(__('Are you sure you want to delete this subscription?'))
            . ','
            . json_encode($this->getDeleteUrl()
            )
            . ')',
                'class' => 'action-default primary'
            ]
        );

        // Split button
        $this->getToolbar()->addChild(
            'save-split-button',
            'Magento\Backend\Block\Widget\Button\SplitButton',
            [
                'id' => 'save-split-button',
                'label' => __('Save'),
                'class_name' => 'Magento\Backend\Block\Widget\Button\SplitButton',
                'button_class' => 'widget-button-save',
                'options' => $this->_getSaveSplitButtonOptions()
            ]
        );        

        return parent::_prepareLayout();
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

        // Form settings
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
        $fieldsCss = 'width:100%;';
        $readOnlyCss = 'color:#777777;background-color:#dddddd;';

        // HTML prefix
        $form->setHtmlIdPrefix('ckom2_grid_');

        // Freeze in edit mode state
        $freezeInEditMode = false;
        $freezeInNewMode = false;

        // If editing data
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Editing payment plan : %1', $model->getName()), 'class' => 'fieldset-wide']
            );

            // Add id field
            $fieldset->addField('id', 'hidden', ['name' => 'id']);

            // Set the frozen field state
            $freezeInEditMode = true;

        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Adding Payment Plan'), 'class' => 'fieldset-wide']
            );

            // Set the frozen field state
            $freezeInNewMode = true;
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
            'plan_status',
            'select',
            [
                'name' => 'plan_status',
                'label' => __('Status'),
                'id' => 'plan_status',
                'title' => __('Status'),
                'values' => $this->_statusOptions->getOptionArray(),
                'class' => 'required-entry',
                'required' => true,
                'disabled' => $freezeInNewMode,
                'style' => $fieldsCss . (($freezeInNewMode) ? $readOnlyCss : ''),
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
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss . $readOnlyCss,
                //'onclick' => "alert('on click');",
                //'onchange' => "alert('on change');",
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
                'after_element_html' => $this->_renderTooltip('Delayed capture time in hours (0 - 168 inclusive) that corresponds to 7 days (24 hrs x 7), for the transactions generated by the recurring engine (e.g. 0.5 is interpreted as 30 mins). Default 0 (captures immediately).'),
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
                'class' => 'required-entry',
                'required' => true,
                'disabled' => $freezeInEditMode,
                'style' => $fieldsCss . (($freezeInEditMode) ? $readOnlyCss : ''),
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
                'readonly' => $freezeInEditMode,
                'style' => $fieldsCss . (($freezeInEditMode) ? $readOnlyCss : ''),
                'after_element_html' => $this->_renderTooltip('Elapsed time between the charge and the first transaction of the recurring plan. Max. 4 chars. Ex: 7d, 2w, 1m, 1y'),
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
                'readonly' => $freezeInEditMode,
                'style' => $fieldsCss . (($freezeInEditMode) ? $readOnlyCss : ''),
                'after_element_html' => $this->_renderTooltip('Number of recurring transactions included in the Payment Plan. This does not include the initial payment.'),
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
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
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
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss . $readOnlyCss,
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }

    /**
     * Render a tooltip
     *
     * @return array
     */
    protected function _renderTooltip($text)
    {
        $output  = '';
        $output .= '<div class="tooltip"><span class="help"><span></span></span><div class="tooltip-content">';
        $output .= __($text);
        $output .= '</div></div>';
        
        return $output;
    }
        
    /**
     * Get dropdown options for save split button
     *
     * @return array
     */
    protected function _getSaveSplitButtonOptions()
    {
        $options = [];
        $options[] = [
            'id' => 'edit-button',
            'label' => __('Save & Edit'),
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndContinueEdit', 'target' => '[data-form=edit-product]'],
                ],
            ],
            'default' => true,
            //'onclick'=>'setLocation("ACTION CONTROLLER")',
        ];

        $options[] = [
            'id' => 'new-button',
            'label' => __('Save & New'),
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndNew', 'target' => '[data-form=edit-product]'],
                ],
            ],
        ];
    
        $options[] = [
            'id' => 'duplicate-button',
            'label' => __('Save & Duplicate'),
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndDuplicate', 'target' => '[data-form=edit-product]'],
                ],
            ],
        ];
    
        $options[] = [
            'id' => 'close-button',
            'label' => __('Save & Close'),
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save', 'target' => '[data-form=edit-product]']],
            ],
        ];
        
        return $options;
    }        
}