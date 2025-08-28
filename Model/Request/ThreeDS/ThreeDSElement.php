<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Request\ThreeDS;

use Checkout\Payments\ThreeDsRequest;
use Checkout\Payments\ThreeDsRequestFactory;
use CheckoutCom\Magento2\Provider\CardPaymentSettings;
use Exception;
use Magento\Store\Model\StoreManagerInterface;

class ThreeDSElement
{
    protected ThreeDsRequestFactory $modelFactory;

    protected CardPaymentSettings $settings;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ThreeDsRequestFactory $modelFactory,
        CardPaymentSettings $settings,
        StoreManagerInterface $storeManager
    ) {
        $this->modelFactory = $modelFactory;
        $this->settings = $settings;
        $this->storeManager = $storeManager;
    }

    public function get(): ThreeDsRequest
    {
        $model = $this->modelFactory->create();

       try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;
        }
        $model->enabled = $this->settings->isThreeDSEnabled($websiteCode);
        $model->attempt_n3d = $this->settings->isAttemptN3DEnabled($websiteCode);

        return $model;
    }
}