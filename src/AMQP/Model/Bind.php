<?php

namespace Anboo\RabbitmqBundle\AMQP\Model;

/**
 * Class Bind
 */
class Bind
{
    /** @var string */
    private $source;

    /** @var string */
    private $destination;

    /** @var string */
    private $destinationType;

    /** @var string */
    private $routingKey;

    /** @var string */
    private $propertiesKey;

    /** @var array */
    private $arguments;

    /** @var string */
    private $vhost;

    /**
     * Bind constructor.
     * @param string $source
     * @param string $destination
     * @param string $destinationType
     * @param string $routingKey
     * @param string $propertiesKey
     * @param array $arguments
     * @param string $vhost
     */
    public function __construct(string $source, string $destination, string $destinationType, string $routingKey, string $propertiesKey, array $arguments, string $vhost)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->destinationType = $destinationType;
        $this->routingKey = $routingKey;
        $this->propertiesKey = $propertiesKey;
        $this->arguments = $arguments;
        $this->vhost = $vhost;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getDestinationType(): string
    {
        return $this->destinationType;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function getPropertiesKey(): string
    {
        return $this->propertiesKey;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getVhost(): string
    {
        return $this->vhost;
    }
}