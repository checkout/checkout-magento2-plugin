<?php

namespace CheckoutCom\Magento2\Ui\Component\Listing\Subscription\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

class UserName extends Column {

    protected $customerRepositoryInterface;

    public function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, CustomerRepositoryInterface $customerRepositoryInterface, array $components = [], array $data = []) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->customerRepositoryInterface = $customerRepositoryInterface;

    }

    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {

            foreach ($dataSource['data']['items'] as & $item) {
                $user = $this->customerRepositoryInterface->getById($item['user_id']);
                $item['user_id'] = $user->getFirstname() . " " . $user->getLastname();
            }
        }

        return $dataSource;
    }
}