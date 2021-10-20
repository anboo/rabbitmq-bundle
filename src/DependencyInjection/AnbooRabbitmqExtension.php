<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 29.12.18
 * Time: 13:28
 */

namespace Anboo\RabbitmqBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Anboo\RabbitmqBundle\DependencyInjection\Configuration;

class AnbooRabbitmqExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $dsn = $config['rabbitmq']['dsn'] ?? null;
        if ($dsn) {
            $parsedDsn = parse_url($_ENV['MESSENGER_TRANSPORT_DSN']);
            if ($parsedDsn) {
                $config['rabbitmq']['host'] = $parsedDsn['host'];
                $config['rabbitmq']['port'] = $parsedDsn['port'];
                $config['rabbitmq']['username'] = $parsedDsn['user'];
                $config['rabbitmq']['password'] = $parsedDsn['pass'];
            }
        }

        $container->setParameter('anboo_rabbitmq.rabbitmq.host', $config['rabbitmq']['host'] ?? '');
        $container->setParameter('anboo_rabbitmq.rabbitmq.port', $config['rabbitmq']['port'] ?? 5672);
        $container->setParameter('anboo_rabbitmq.rabbitmq.username', $config['rabbitmq']['username'] ?? 'guest');
        $container->setParameter('anboo_rabbitmq.rabbitmq.password', $config['rabbitmq']['password'] ?? 'guest');
        $container->setParameter('anboo_rabbitmq.rabbitmq.vhost', $config['rabbitmq']['vhost'] ?? '/');
        $container->setParameter('anboo_rabbitmq.rabbitmq.http_port', $config['rabbitmq']['http_port'] ?? '15672');
        $container->setParameter('anboo_rabbitmq.rabbitmq.protocol', $config['rabbitmq']['http_protocol'] ?? 'http');
        $container->setParameter('anboo_rabbitmq.rabbitmq.rpc_response_storage', $config['rabbitmq']['rpc_response_storage'] ?? '');
        $container->setParameter('anboo_rabbitmq.rabbitmq.rpc_response_queue', $config['rabbitmq']['rpc_response_queue'] ?? '');
        $container->setParameter('anboo_rabbitmq.rabbitmq.rpc_response_frontend_queue_prefix', $config['rabbitmq']['rpc_response_frontend_queue_prefix'] ?? '');
        $container->setParameter('anboo_rabbitmq.rabbitmq.lifetime_callback_rpc_queue', $config['rabbitmq']['lifetime_callback_rpc_queue'] ?? 30);

        if (isset($config['rabbitmq']) && isset($config['rabbitmq']['redis'])) {
            $container->setParameter('anboo_rabbitmq.rabbitmq.redis.host', $config['rabbitmq']['redis']['host'] ?? '');
            $container->setParameter('anboo_rabbitmq.rabbitmq.redis.port', $config['rabbitmq']['redis']['port'] ?? '');
            $container->setParameter('anboo_rabbitmq.rabbitmq.redis.scheme', $config['rabbitmq']['redis']['scheme'] ?? '');
        }
    }
}
