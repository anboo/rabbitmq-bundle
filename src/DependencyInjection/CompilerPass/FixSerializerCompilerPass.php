<?php

namespace Anboo\ApiBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FixSerializerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('serializer.normalizer.object')) {
            return;
        }

        $setCircularReferenceHandlerProxyArguments = $setMaxDepthHandlerProxyArguments = null;

        $objectNormalizerDefinition = $container->getDefinition('serializer.normalizer.object');
        $methodsClass = $objectNormalizerDefinition->getMethodCalls();
        foreach ($methodsClass as $methodClass) {
            if ('setCircularReferenceHandler' === $methodClass[0]) {
                $setCircularReferenceHandlerProxyArguments = $methodClass[1];
            }

            if ('setMaxDepthHandler' === $methodClass[0]) {
                $setMaxDepthHandlerProxyArguments = $methodClass[1];
            }
        }

        foreach ($container->findTaggedServiceIds('serializer.normalizer') as $serviceId => $serviceData) {
            $definition = $container->getDefinition($serviceId);

            if (!$definition || !$class = $definition->getClass()) {
                continue;
            }

            if (is_subclass_of($class, ObjectNormalizer::class)) {
                if ($setCircularReferenceHandlerProxyArguments) {
                    $definition->addMethodCall('setCircularReferenceHandler', $setCircularReferenceHandlerProxyArguments);
                }

                if ($setMaxDepthHandlerProxyArguments) {
                    $definition->addMethodCall('setMaxDepthHandler', $setMaxDepthHandlerProxyArguments);
                }
            }
        }
    }
}