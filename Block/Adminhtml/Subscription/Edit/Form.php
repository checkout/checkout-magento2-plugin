<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Block\Adminhtml\Subscription\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
use CheckoutCom\Magento2\Model\Fields\SubscriptionStatus;
use CheckoutCom\Magento2\Model\Fields\Currency;
use CheckoutCom\Magento2\Model\Fields\PaymentPlan;
use CheckoutCom\Magento2\Model\Fields\UserList;
use CheckoutCom\Magento2\Model\Fields\UserCards;

/**
 * Adminhtml Add New Row Form.
 */
class Form extends Generic 
{

    protected $_systemStore;
    protected $_statusOptions;
    protected $_currencyOptions;
    protected $_paymentPlanOptions;
    protected $_userListOptions;
    protected $_userCardsOptions;
    protected $_wysiwygConfig;
 
    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Config $wysiwygConfig, SubscriptionStatus $statusOptions, PaymentPlan $paymentPlanOptions, Currency $currencyOptions, UserList $userListOptions, UserCards $userCardsOptions, array $data = []) 
    {
        $this->_statusOptions = $statusOptions;
        $this->_currencyOptions = $currencyOptions;
        $this->_paymentPlanOptions = $paymentPlanOptions;
        $this->_userListOptions = $userListOptions;
        $this->_userCardsOptions = $userCardsOptions;
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

        // Build the form
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Editing subscription : %1', $model->getTrackId()), 'class' => 'fieldset-wide']
            );

            // Add id field
            $fieldset->addField('id', 'hidden', ['name' => 'id']);

            // Set the frozen field state
            $freezeInEditMode = true;

        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Adding Subscription'), 'class' => 'fieldset-wide']
            );

            // Set the frozen field state
            $freezeInNewMode = true;
        }

        $fieldset->addField(
            'subscription_status',
            'select',
            [
                'name' => 'subscription_status',
                'label' => __('Status'),
                'id' => 'subscription_status',
                'title' => __('Status'),
                'values' => $this->_statusOptions->getOptionArray(),
                'class' => 'status',
                'required' => true,
                'disabled' => $freezeInNewMode,
                'style' => $fieldsCss . (($freezeInNewMode) ? $readOnlyCss : ''),
            ]
        );

        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Start date'),
                'id' => 'start_date',
                'title' => __('Start date'),
                'class' => 'required-entry',
                'required' => true,
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'hh:mm:ss',
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'card_id',
            'select',
            [
                'name' => 'card_id',
                'label' => __('Payment method'),
                'id' => 'card_id',
                'title' => __('Payment method'),
                'values' => $this->_userCardsOptions->getOptions(),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
                'after_element_html' => '<div></div>',
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
                'style' => $fieldsCss. $readOnlyCss,
            ]
        );
        
        $fieldset->addField(
            'plan_id',
            'select',
            [
                'name' => 'plan_id',
                'label' => __('Payment plan'),
                'id' => 'track_id',
                'title' => __('Payment plan'),
                'values' => $this->_paymentPlanOptions->getOptionArray(),
                'class' => 'required-entry',
                'required' => true,
                'disabled' => $freezeInEditMode,
                'style' => $fieldsCss . (($freezeInEditMode) ? $readOnlyCss : ''),
            ]
        );

        $fieldset->addField(
            'user_id',
            'select',
            [
                'name' => 'user_id',
                'label' => __('Customer'),
                'id' => 'user_id',
                'title' => __('Customer'),
                'values' => $this->_userListOptions->getOptionArray(),
                'class' => 'required-entry',
                'required' => true,
                'disabled' => $freezeInEditMode,
                'style' => $fieldsCss . (($freezeInEditMode) ? $readOnlyCss : ''),
            ]
        );

        $fieldset->addField(
            'recurring_count_left',
            'text',
            [
                'name' => 'recurring_count_left',
                'label' => __('Recurring count left'),
                'id' => 'recurring_count_left',
                'title' => __('Recurring count left'),
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss. $readOnlyCss,
                'after_element_html' => $this->_renderTooltip('Number of transactions remaining in the recurring plan.'),
            ]
        );

        $fieldset->addField(
            'total_collection_count',
            'text',
            [
                'name' => 'total_collection_count',
                'label' => __('Total collection count'),
                'id' => 'total_collection_count',
                'title' => __('Total collection count'),
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss. $readOnlyCss,
                'after_element_html' => $this->_renderTooltip('Total number of transactions that will be applied against the card.'),
            ]
        );

        $fieldset->addField(
            'total_collection_value',
            'text',
            [
                'name' => 'total_collection_value',
                'label' => __('Total collection value'),
                'id' => 'total_collection_value',
                'title' => __('Total collection value'),
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss. $readOnlyCss,
                'after_element_html' => $this->_renderTooltip('Total value of transactions that will be applied against the card.'),
            ]
        );

        $fieldset->addField(
            'previous_recurring_date',
            'text',
            [
                'name' => 'previous_recurring_date',
                'label' => __('Previous recurring date'),
                'id' => 'previous_recurring_date',
                'title' => __('Previous recurring date'),
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss. $readOnlyCss,
                'after_element_html' => $this->_renderTooltip('Date of last recurring transaction in "YYYY-MM-DD" format.'),
            ]
        );

        $fieldset->addField(
            'next_recurring_date',
            'text',
            [
                'name' => 'next_recurring_date',
                'label' => __('Next recurring date'),
                'id' => 'next_recurring_date',
                'title' => __('Next recurring date'),
                'placeholder' => __('Generated automatically'),
                'readonly' => true,
                'style' => $fieldsCss. $readOnlyCss,
                'after_element_html' => $this->_renderTooltip('Date of the next recurring transaction in "YYYY-MM-DD" format. This is especially useful when the merchant has applied a double recurring plan.'),
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