<?php

namespace Anboo\ApiBundle\AMQP\RPC;

/**
 * Class Destination
 */
class Exchange
{
    /** @var string|null */
    private $exchange;

    /** @var string|null */
    private $routingKey;

    /**
     * Destination constructor.
     * @param string|null $exchange
     * @param string|null $routingKey
     */
    public function __construct(?string $exchange, ?string $routingKey)
    {
        if (is_null($exchange) && is_null($routingKey)) {
            throw new \RuntimeException(sprintf('You need provide exchange and routing key or routingKey'));
        }

        $this->exchange = $exchange ?? '';
        $this->routingKey = $routingKey;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange ?? '';
    }

    /**
     * @return string|null
     */
    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }

    /**
     * @return bool
     */
    public function isQueueDestination() : bool
    {
        return !$this->exchange && !empty($this->routingKey);
    }

    /**
     * @return bool
     */
    public function isExchangeDestination() : bool
    {
        return !empty($this->exchange) && !empty($this->routingKey);
    }
}