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

    public function append(PaymentSessionsRequest|PaymentLinkRequest $payload): void
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

        $availablePaymentMethods = $this->checkCountry($availablePaymentMethods, $allPaymentMethods, $payload);
        $availablePaymentMethods = $this->checkCurrency($availablePaymentMethods, $allPaymentMethods, $payload);
        $availablePaymentMethods = $this->checkPaymentType($availablePaymentMethods, $allPaymentMethods, $payload);
        $availablePaymentMethods = $this->checkMandatoriesFields($availablePaymentMethods, $allPaymentMethods, $payload);

        $payload->enabled_payment_methods = array_values($availablePaymentMethods);
        $payload->disabled_payment_methods = array_values($this->getDisabledMethods($allPaymentMethods, $availablePaymentMethods));
    }

    protected function getEnabled(?string $websiteCode): array
    {
        return $this->flowPaymentMethodSettings->getEnabledPaymentMethods($websiteCode);
    }

    protected function checkCountry(array $payments, array $definition, PaymentSessionsRequest $request): array 
    {
        return $this->doCheck(
            $request->billing->address->country ?? '',
            'countries', 
            $payments, 
            $definition
        );
    }

    protected function checkCurrency(array $payments, array $definition, PaymentSessionsRequest $request): array 
    {
        return $this->doCheck(
            $request->currency ?? '',
            'currency', 
            $payments, 
            $definition
        );
    }

    protected function checkPaymentType(array $payments, array $definition, PaymentSessionsRequest $request): array 
    {
        return $this->doCheck(
            $request->payment_type ?? '',
            'paymentType', 
            $payments, 
            $definition
        );
    }

    protected function doCheck(mixed $requestProperty, string $definitionProperty, array $payments, array $definition): array {
        return array_filter($payments, function($method) use($requestProperty, $definitionProperty, $definition){
            $valueToCheck =  $definition[$method][$definitionProperty] ?? '';
            
            return empty($valueToCheck) || in_array($requestProperty, explode(',', $valueToCheck));
        });
    }

    protected function checkMandatoriesFields(array $payments, array $definition, PaymentSessionsRequest $request): array
    {
        $email = $request->customer->email ?? '';
        $description = $request->description ?? '';
        $reference = $request->reference ?? '';
        
        $afterEmailCheck = $this->doControl($email, 'emailMandatory', $payments, $definition);
        $afterDefinitionCheck = $this->doControl($description, 'descriptionMandatory', $afterEmailCheck, $definition);
        $afterReferenceCheck = $this->doControl($reference, 'referenceMandatory', $afterDefinitionCheck, $definition);

        return $afterReferenceCheck;
    }

    protected function doControl(mixed $property, string $definitionProperty, array $payments, array $definition): array
    {
        if(!empty($property)) {
            return $payments;
        }

        return array_filter($payments, function($method) use($definitionProperty, $definition){
            $isDefinitionRequired = $definition[$method][$definitionProperty] ?? false;
            
            return !$isDefinitionRequired;
        });
    }

    protected function getDisabledMethods(array $allPaymentMethods, array $availablePaymentMethods): array
    {
        $allMethodNames = array_keys($allPaymentMethods);
        $unusedMethods = array_diff($allMethodNames, $availablePaymentMethods);
        return $unusedMethods;
    }
}
