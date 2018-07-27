<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
namespace CheckoutCom\Magento2\Gateway\Request;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use CheckoutCom\Magento2\Helper\Helper;

class MetaDataRequest implements BuilderInterface {

    /**
     * @var ProductMetadataInterface
     */
    protected $metadata;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * MetaDataRequest constructor.
     * @param ProductMetadataInterface $metadata
     * @param ModuleListInterface $moduleList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(ProductMetadataInterface $metadata, Helper $helper, ModuleListInterface $moduleList, StoreManagerInterface $storeManager) {
        $this->metadata        = $metadata;
        $this->helper          = $helper;
        $this->moduleList      = $moduleList;
        $this->storeManager    = $storeManager;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject) {
        return [
            'metadata' => [
                'magento_name'      => $this->metadata->getName(),
                'magento_edition'   => $this->metadata->getEdition(),
                'magento_version'   => $this->metadata->getVersion(),
                'setup_version'     => $this->moduleList->getOne('CheckoutCom_Magento2')['setup_version'],
                'module_version'    => $this->helper->getModuleVersion(),
                'store_id'          => $this->storeManager->getStore()->getId(),
            ],
        ];
    }


    

}
