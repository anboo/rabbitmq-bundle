<?php

namespace Anboo\RabbitmqBundle\Event;

use Anboo\RabbitmqBundle\AMQP\Router\RouterCollection;
use Symfony\Contracts\EventDispatcher\Event;

class PreLoadRoutingConsumerEvent extends Event
{
    const NAME = 'anboo.rabbitmq_bundle.pre_load_routing_consumer_event';

    /** @var RouterCollection */
    private $routeCollection;

    /**
     * PreStartConsumerEvent constructor.
     * @param RouterCollection $routeCollection
     */
    public function __construct(RouterCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
    }

    /**
     * @return RouterCollection
     */
    public function getRouteCollection(): RouterCollection
    {
        return $this->routeCollection;
    }
}