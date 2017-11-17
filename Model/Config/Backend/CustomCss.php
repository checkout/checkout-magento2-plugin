<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
  
namespace CheckoutCom\Magento2\Model\Config\Backend;

use \Magento\Config\Model\Config\Backend\File;
 
class CustomCss extends File
{

    const UPLOAD_DIR = 'checkout_com';

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */    
    protected function _getAllowedExtensions() {
        return ['css'];
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo() {
        return true;
    }

    protected function getTmpFileName() {
        $tmpName = null;
        $value = $this->getValue();

        $tmpName = (is_array($value) && isset($value['tmp_name'])) ? $value['tmp_name'] : null;

        return $tmpName;
    }

    public function beforeSave() {
        $value = $this->getValue();
        $deleteFlag = is_array($value) && !empty($value['delete']);
        $fileTmpName = $this->getTmpFileName();

        if ($this->getOldValue() && ($fileTmpName || $deleteFlag)) {
            $this->_mediaDirectory->delete(self::UPLOAD_DIR . '/' . $this->getOldValue());
        }

        return parent::beforeSave();
    }

}