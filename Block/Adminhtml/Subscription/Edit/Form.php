<?php
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
    protected $_wysiwygConfig;
 
    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Config $wysiwygConfig, SubscriptionStatus $statusOptions, PaymentPlan $paymentPlanOptions, Currency $currencyOptions, UserList $userListOptions, array $data = []) 
    {
        $this->_statusOptions = $statusOptions;
        $this->_currencyOptions = $currencyOptions;
        $this->_paymentPlanOptions = $paymentPlanOptions;
        $this->_userListOptions = $userListOptions;
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
                ['legend' => __('Adding Subscription'), 'class' => 'fieldset-wide']
            );
        }

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
                'style' => $fieldsCss,
            ]
        );

        $fieldset->addField(
            'card_id',
            'text',
            [
                'name' => 'card_id',
                'label' => __('Card ID'),
                'id' => 'card_id',
                'title' => __('Card ID'),
                'class' => 'required-entry',
                'readonly' => true,
                'required' => true,
                'style' => $fieldsCss . $readOnlyCss,
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
                'style' => $fieldsCss,
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
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
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
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
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
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
            ]
        );

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
                'style' => $fieldsCss,
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
            'previous_recurring_date',
            'text',
            [
                'name' => 'previous_recurring_date',
                'label' => __('Previous recurring date'),
                'id' => 'previous_recurring_date',
                'title' => __('Previous recurring date'),
                'class' => 'required-entry',
                'required' => true,
                'style' => $fieldsCss,
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
                'class' => 'required-entry',
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