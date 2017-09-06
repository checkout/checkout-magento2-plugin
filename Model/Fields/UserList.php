<?php
namespace CheckoutCom\Magento2\Model\Fields;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class UserList implements OptionSourceInterface
{

    protected $resource;
    protected $customerGroup;
    protected $customerCollectionFactory;

    public function __construct(Collection $customerGroup, CollectionFactory $customerCollectionFactory) {
        $this->customerGroup = $customerGroup;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public function getOptionArray()
    {
        $options = $this->getData();

        return $options;
    }
 
    /**
     * Get Grid row status labels array with empty value for option element.
     * @return array
     */
    public function getAllOptions()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }
 
    /**
     * Get Grid row type array for option element.
     * @return array
     */
    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * Get the data from DB.
     * @return array
     */
    public function getData()
    {
        // Prepare the output array
        $output = [];
        
        // Get the customer groups
        $customerGroups = $this->customerGroup->toOptionArray();

        // Get users for each group
        foreach ($customerGroups as $customerGroup) {

            $collection = $this->customerCollectionFactory->create();
            $collection->addFieldToFilter('group_id', $customerGroup['value']);

            $options = [];
            foreach ($collection as $user) {
                $options[] = [
                    'label' => $user->getFirstname() . " " . $user->getLastname(),
                    'value' => $user->getEntityId()
                ];
            }

            // Add the options to the subgroup
            $subGroup = [
                'label' => $customerGroup['label'],
                'value' => $options
            ];

            // Add the subgroup to the stack
            $output[] = $subGroup;
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}