<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsProvider
{
    const NAME = 'shipping_methods_data_provider';

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var ShippingRulesProvider */
    protected $shippingRulesProvider;

    /** @var  ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /**
     * @param ShippingMethodRegistry $registry
     * @param ShippingRulesProvider $shippingRuleProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $registry,
        ShippingRulesProvider $shippingRuleProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->registry = $registry;
        $this->shippingRulesProvider = $shippingRuleProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * @param Checkout $entity
     * @return array
     */
    public function getMethods(Checkout $entity)
    {
        if (null !== $entity) {
            $shippingContext = $this->shippingContextProviderFactory->create($entity);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
            return $this->calculateApplicableShippingMethods($shippingContext, $rules);
        } else {
            return null;
        }
    }

    /**
     * @param ShippingContextAwareInterface $context
     * @param ShippingRule[]|array $applicableRules
     * @return array
     */
    protected function calculateApplicableShippingMethods(
        ShippingContextAwareInterface $context,
        array $applicableRules
    ) {
        $shippingMethods = [];
        foreach ($applicableRules as $rule) {
            /** @var ShippingRuleConfiguration $configuration */
            $configurations = $rule->getConfigurations();
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                $method = $this->registry->getShippingMethod($methodName);
                if (null !== $method) {
                    if (!$typeName || $typeName === $methodName) {
                        if (!array_key_exists($methodName, $shippingMethods)) {
                            $shippingMethods[$methodName] = [
                                'name' => $methodName,
                                'label' => $method->getLabel(),
                                'price' => $method->calculatePrice($context, $configuration),
                                'shippingRuleConfig' => $configuration->getId(),
                            ];
                        }
                    } else {
                        if (!array_key_exists($methodName, $shippingMethods)) {
                            $shippingMethods[$methodName] = [
                                'name' => $methodName,
                                'label' => $method->getLabel(),
                                'types' => []
                            ];
                        }
                        if (!array_key_exists($typeName, $shippingMethods[$methodName])) {
                            $shippingMethods[$methodName]['types'][$typeName] = [
                                'name' => $typeName,
                                'label' => $method->getShippingTypeLabel($typeName),
                                'price' => $method->calculatePrice($context, $configuration),
                                'shippingRuleConfig' => $configuration->getId(),
                            ];
                        }
                    }
                }
            }
        }

        return $shippingMethods;
    }
}
