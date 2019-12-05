<?php

namespace Anboo\RabbitmqBundle\AMQP\Router;

/**
 * Class RouterCollection
 */
class RouterCollection
{
    /**
     * @var array
     */
    private $routes;

    /**
     * Router constructor.
     * @param Route[] $routes
     */
    public function __construct (array $routes)
    {
        $this->routes = [];

        foreach ($routes as $route) {
            if (is_array($route)) {
                $newRoute = new Route(
                    $route['name'],
                    $route['queue'],
                    $route['itemType'],
                    isset($route['exchange_name']) ? $route['exchange_name'] : null,
                    isset($route['exchange_bind_key']) ? $route['exchange_bind_key'] : null,
                    $route['consumer'],
                    $route['action'],
                    $route['onErrors']
                );

                $newRoute->setExchangeParameters($route['exchange_parameters'] ?? []);
                $newRoute->setQueueParameters($route['queue_parameters'] ?? []);
                $newRoute->setConsumerParameters($route['consumer_parameters'] ?? []);

                $this->routes[] = $newRoute;
            } else {
                $this->routes[] = $route;
            }
        }
    }

    /**
     * @return Route[]
     */
    public function all()
    {
        return $this->routes;
    }

    /**
     * @param string $queueName
     * @return Route|null
     */
    public function getRouteByQueueName(string $queueName)
    {
        foreach ($this->routes as $route) {
            if ($route->getQueue() == $queueName) {
                return $route;
            }
        }

        return null;
    }
}