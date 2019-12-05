<?php

namespace Anboo\RabbitmqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('anboo_rabbitmq');
        $rootNode->children()
                    ->arrayNode('rabbitmq')
                        ->children()
                            ->scalarNode('host')->end()
                            ->integerNode('port')->end()
                            ->integerNode('http_port')->end()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('vhost')->end()
                            ->scalarNode('http_protocol')->defaultValue('http')->end()
                            ->scalarNode('rpc_response_storage')->defaultValue('redis')->end()
                            ->scalarNode('rpc_response_queue')->end()
                            ->scalarNode('rpc_response_frontend_queue_prefix')->end()
                            ->integerNode('lifetime_callback_rpc_queue')->defaultValue(30)->end()
                            ->arrayNode('redis')
                                ->children()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('scheme')->defaultValue('tcp')->end()
                                    ->integerNode('port')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                 ->end()
        ;

        return $treeBuilder;
    }
}
