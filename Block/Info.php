<?php

namespace CheckoutCom\Magento2\Block;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $source = $this->getSource();
        $data = [];
        if ($ccType = $source->getScheme()) {
            $data[(string)__('Credit Card Type')] = $ccType;
        }
        if ($last4 = $source->getData('last4')) {
            $data[(string)__('Credit Card Number')] = sprintf('xxxx-%s', $last4);
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    /**
     * Retrieve Source info
     *
     * @return \Magento\Framework\DataObject
     */
    private function getSource()
    {
        $source = [];

        $info = $this->getInfo();

        // backend implementation
        if ($info === null) {
            $payment = $this->getParentBlock()->getPayment()?:  new \Magento\Framework\DataObject($source);
            $additionalInfo = $payment->getAdditionalInformation();
        } else {
            // frontend implementation
            $additionalInfo = $info->getAdditionalInformation();
        }

        if ($info !== null) {
            $source = $additionalInfo['transaction_info']['source'] ?? [];
        }

        return new \Magento\Framework\DataObject($source);
    }
}
