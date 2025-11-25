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

namespace CheckoutCom\Magento2\Model\Request\PaymentMethodAvailability;

use Checkout\Payments\Sessions\PaymentSessionsRequest;
use CheckoutCom\Magento2\Model\Request\Additionnals\PaymentLinkRequest;
use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Exception;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class EnabledDisabledElement
{
    protected FlowPaymentMethodSettings $flowPaymentMethodSettings;
    protected LoggerInterface $logger;
    private StoreManagerInterface $storeManager;

    public function __construct(
        FlowPaymentMethodSettings $flowPaymentMethodSettings,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->flowPaymentMethodSettings = $flowPaymentMethodSettings;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param PaymentSessionsRequest|PaymentLinkRequest $payload
     *
     * @return array
     */
    public function get($payload): array
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Exception $error) {
            $websiteCode = null;

            $this->logger->error(
                sprintf("Unable to fetch website code: %s", $error->getMessage()),
            );
        }

        $allPaymentMethods = $this->flowPaymentMethodSettings->getAllPaymentMethods();

        $availablePaymentMethods = $this->getEnabled($websiteCode);

        $availablePaymentMethods = $this->doCheck(
            $payload->billing->address->country ?? '',
            'countries',
            $availablePaymentMethods,
            $allPaymentMethods
        );

        $availablePaymentMethods = $this->doCheck(
            $payload->currency ?? '',
            'currencies',
            $availablePaymentMethods,
            $allPaymentMethods
        );

        $availablePaymentMethods = $this->doCheck(
            $payload->payment_type ?? '',
            'paymentType',
            $availablePaymentMethods,
            $allPaymentMethods
        );

        $availablePaymentMethods = $this->checkMandatoriesFields($availablePaymentMethods, $allPaymentMethods, $payload);

        $result = [
            'enabled_payment_methods' => array_values($availablePaymentMethods),
            'disabled_payment_methods' => array_values($this->getDisabledMethods($allPaymentMethods, $availablePaymentMethods))
        ];

        return $result;
    }

    protected function getEnabled(?string $websiteCode): array
    {
        return $this->flowPaymentMethodSettings->getEnabledPaymentMethods($websiteCode);
    }

    /**
     * @param mixed $requestProperty
     * @param string $definitionProperty
     * @param array $payments
     * @param array $definition
     *
     * @return array
     */
    protected function doCheck($requestProperty, string $definitionProperty, array $payments, array $definition): array {
        return array_filter($payments, function($method) use($requestProperty, $definitionProperty, $definition){
            $valueToCheck =  $definition[$method][$definitionProperty] ?? '';

            return empty($valueToCheck) || in_array($requestProperty, explode(',', $valueToCheck));
        });
    }

    /**
     * @param array $payments
     * @param array $definition
     * @param PaymentSessionsRequest|PaymentLinkRequest $request
     *
     * @return array
     */
    protected function checkMandatoriesFields(array $payments, array $definition, $request): array
    {
        $email = $request->customer->email ?? '';
        $description = $request->description ?? '';
        $reference = $request->reference ?? '';
        $shipping = $request->shipping->address;

        $afterEmailCheck = $this->doEmptyControl($email, 'emailMandatory', $payments, $definition);
        $afterDefinitionCheck = $this->doEmptyControl($description, 'descriptionMandatory', $afterEmailCheck, $definition);
        $afterReferenceCheck = $this->doEmptyControl($reference, 'referenceMandatory', $afterDefinitionCheck, $definition);
        $afterShippingCheck = $this->doObjectControl($shipping, 'shipping', $afterReferenceCheck, $definition);

        return $afterShippingCheck;
    }

    /**
     * @param mixed $property
     * @param string $definitionProperty
     * @param array $payments
     * @param array $definition
     *
     * @return array
     */
    protected function doEmptyControl($property, string $definitionProperty, array $payments, array $definition): array
    {
        if(!empty($property)) {
            return $payments;
        }

        return array_filter($payments, function($method) use($definitionProperty, $definition){
            $isDefinitionRequired = $definition[$method][$definitionProperty] ?? false;

            return !$isDefinitionRequired;
        });
    }

    /**
     * @param mixed $object
     * @param string $definitionProperty
     * @param array $payments
     * @param array $definition
     *
     * @return array
     */
    protected function doObjectControl($object, string $definitionProperty, array $payments, array $definition): array
    {
        return array_filter($payments, function($method) use($object, $definitionProperty, $definition) {
            $configuration = $definition[$method][$definitionProperty] ?? '';
            
            if (empty($configuration)) {
                return true;
            }

            $requiredProperties = explode(',', $configuration);

            $objectProperties = get_object_vars($object);
            return array_all($requiredProperties, function($property) use($objectProperties) {
                return !empty($objectProperties[$property] ?? null);
            });
        });
    }

    protected function getDisabledMethods(array $allPaymentMethods, array $availablePaymentMethods): array
    {
        $allMethodNames = array_keys($allPaymentMethods);
        $unusedMethods = array_diff($allMethodNames, $availablePaymentMethods);

        return $unusedMethods;
    }
}
