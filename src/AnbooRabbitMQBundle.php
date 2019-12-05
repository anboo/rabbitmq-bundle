<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 06.12.18
 * Time: 7:49
 */

namespace Anboo\RabbitmqBundle;

use Anboo\RabbitmqBundle\DependencyInjection\AnbooRabbitmqExtension;
use Anboo\RabbitmqBundle\DependencyInjection\CompilerPass\AmqpConsumerPass;
use Anboo\RabbitmqBundle\DependencyInjection\CompilerPass\FixSerializerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AnbooRabbitMQBundle extends Bundle
{
    /**
     * @return mixed
     */
    public function getContainerExtension()
    {
        return new AnbooRabbitmqExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AmqpConsumerPass());
    }
}