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

namespace CheckoutCom\Magento2\Model\Request\Risk;

use Checkout\Payments\RiskRequest;
use Checkout\Payments\RiskRequestFactory;

/**
 * Class RiskElement
 */
class RiskElement
{
    protected RiskRequestFactory $modelFactory;

    public function __construct(
        RiskRequestFactory $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function get(): RiskRequest {
        $model = $this->modelFactory->create();

        $model->enabled = true;

        return $model;
    }
}