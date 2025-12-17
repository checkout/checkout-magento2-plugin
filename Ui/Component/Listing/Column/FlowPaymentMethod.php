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
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class FlowPaymentMethod extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return;
        }

        foreach ($dataSource['data']['items'] as $index => $order) {
            if (!isset($order['payment_additional_information'])) {
                continue;
            }

            $payment = json_decode($order['payment_additional_information']);
            $flowMethodId = isset($payment->flow_method_id) ? $payment->flow_method_id : '';

            $dataSource['data']['items'][$index]['payment_additional_information'] = $flowMethodId;
        }

        return $dataSource;
    }
}
