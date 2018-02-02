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
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Payment\Model\CcConfigProvider;

class UserCards implements OptionSourceInterface
{

    protected $paymentTokenManagement;
    protected $session;

    /**
     * @var CcConfigProvider
     */
    private $iconsProvider;

    public function __construct(PaymentTokenManagementInterface $paymentTokenManagement, Session $session, CcConfigProvider $iconsProvider) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->session = $session;
        $this->iconsProvider = $iconsProvider;
    }

    /**
     * Get Grid row type labels array.
     * @return array
     */
    public function getOptionArray()
    {
        // Prepare the options array
        $options = [];

        // Get the customer id 
        // TODO get from model or user select
        $customerId = 2;

        // Get the cards list
        $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);

        // Prepare the options list
        foreach ($cardList as $card) {
            // Get the card data
            $cardData = $card->getData();

            // Create the option
            //if ((int) $cardData->is_active == 1 && (int) $cardData->is_visible == 1) {
            if ($cardData) {
                $options[$cardData['gateway_token']] = $this->_getOptionString(json_decode($cardData['details']));
            }
        }

        return $options;
    }
 
    protected function _getOptionString($details)
    {
        $output  = '';
        $output .= isset($details->type) ? ':' . $details->type : '';
        $output .= ' | ';
        $output .= isset($details->maskedCC) ? __('Last 4 digits') . ' : ' . $details->maskedCC : '';
        $output .= ' | ';
        $output .= isset($details->expirationDate) ?  __('Expires') . ' : ' . $details->expirationDate : '';

        return $output;
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