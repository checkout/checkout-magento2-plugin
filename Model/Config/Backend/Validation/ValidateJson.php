<?php
namespace CheckoutCom\Magento2\Model\Config\Backend\Validation;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class ValidateJson extends Value
{
    /**
     * Validate JSON before save
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (!empty($value)) {
            if (!json_decode($value)) {
                throw new LocalizedException(__('The value must be a valid JSON string.'));
            }
        }
        
        return parent::beforeSave();
    }
}