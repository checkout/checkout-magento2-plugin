<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Fields;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\ResourceConnection;

class PaymentPlan implements OptionSourceInterface
{

    protected $resource;

    public function __construct(ResourceConnection $resource) {
        $this->resource = $resource;
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
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $result = $connection->fetchAll('SELECT id, plan_name FROM `cko_m2_plans`');

        return $this->formatData($result);
    }

    /**
     * Format the data from DB.
     * @return array
     */
    public function formatData(array $rows)
    {
        $options = [];

        if (($rows) && count($rows) > 0) {
            foreach ($rows as $row) {
                $options[$row['id']] = $row['plan_name'];
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}