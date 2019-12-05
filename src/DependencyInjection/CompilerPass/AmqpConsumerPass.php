<?php

namespace Anboo\RabbitmqBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Anboo\RabbitmqBundle\AMQP\Router\Route;
use Anboo\RabbitmqBundle\Annotation\Enqueue\Consume;
use Anboo\RabbitmqBundle\AMQP\Router\RouterCollection;
use Anboo\RabbitmqBundle\AMQP\RPC\FileSystemResponseStorage;
use Anboo\RabbitmqBundle\AMQP\RPC\ResponseStorageInterface;

/**
 * Class AmqpConsumerPass
 */
final class AmqpConsumerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $annotationReader = $container->get('annotation_reader');

        $routeCollectionServiceId = RouterCollection::class;
        $routeCollectionDefinition = $container->getDefinition($routeCollectionServiceId);

        $routes = [];
        foreach ($container->findTaggedServiceIds('anbooqueue_consumer') as $serviceId => $serviceData) {
            $reflectionClass = new \ReflectionClass(
                $container->getDefinition($serviceId)->getClass()
            );

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                /** @var Consume $consume */
                $consume = $annotationReader->getMethodAnnotation($reflectionMethod, Consume::class);
                if (!$consume) {
                    continue;
                }

                $routes[] = Route::createFromConsume($consume, $serviceId, $reflectionMethod->getName());
            }
        }

        $routeCollectionDefinition->replaceArgument(0, array_merge(
            $routeCollectionDefinition->getArgument(0),
            $routes
        ));

        $container->register(FileSystemResponseStorage::class, FileSystemResponseStorage::class);
        $container->setAlias(ResponseStorageInterface::class, FileSystemResponseStorage::class);
    }
}
