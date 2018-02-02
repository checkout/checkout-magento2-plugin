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
use Magento\Config\Model\Config\Source\Locale\Currency as CurrencyManager;

class Currency implements OptionSourceInterface
{
    protected $currencyManager;

    public function __construct(CurrencyManager $currencyManager) 
    {
        $this->currencyManager = $currencyManager;
    }

    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public function getOptionArray()
    {
        $options = $this->currencyManager->toOptionArray();

        return $options;
    }
 
    /**
     * Get Grid row status labels array with empty value for option element.
     *
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
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}